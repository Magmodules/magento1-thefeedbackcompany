<?php
/**
 * Magmodules.eu - http://www.magmodules.eu
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Feedbackcompany
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Feedbackcompany_Model_Productreviews extends Magmodules_Feedbackcompany_Model_Api
{

    const FBC_REVIEW_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/getrecent/?type=product&interval=last_week';
    const FBC_REVIEW_URL_FULL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/all/?type=product';
    const XML_REVIEW_IMPORT_STATUS = 'feedbackcompany/productreviews/review_import_status';
    const XML_REVIEW_IMPORT_RATING = 'feedbackcompany/productreviews/review_import_rating';
    const XML_LAST_RUN = 'feedbackcompany/productreviews/lastrun';

    public $new = 0;

    /**
     * @param $storeId
     * @param $type
     *
     * @return array
     */
    public function runUpdate($storeId, $type = 'last_week')
    {
        $feed = $this->getFeed($storeId, $type);

        if ($feed['status'] != 'OK') {
            return $feed;
        }

        if (!isset($feed['feed']['product_reviews'])) {
            return array();
        }

        $statusId = Mage::getStoreConfig(self::XML_REVIEW_IMPORT_STATUS, $storeId);
        $ratingId = Mage::getStoreConfig(self::XML_REVIEW_IMPORT_RATING, $storeId);
        $options = $this->getRatingOptionArray($ratingId);
        $storeViews = $this->getAllStoreViews($storeId);

        foreach ($feed['feed']['product_reviews'] as $review) {
            $reviewId = $this->getReviewIdByFeedbackId($review['id']);
            if (!empty($reviewId)) {
                continue;
            }

            if ($review['rating'] < 1) {
                continue;
            }

            $productId = Mage::getModel("catalog/product")->getIdBySku($review['product_sku']);
            if (!$productId) {
                continue;
                //$productId = '905';
            }

            try {
                $content = $review['review'];
                $title = $this->getFirstSentence($content);
                if (!empty($title)) {

                    $createdAt = $this->reformatDate($review['date_created']);
                    $nickName = $review['client']['name'];
                    $ratingVal = $review['rating'];
                    $id = $review['id'];

                    $review = Mage::getModel('review/review');
                    $review->setEntityPkValue($productId);
                    $review->setCreatedAt($createdAt);
                    $review->setTitle($title);
                    $review->setFeedbackcompanyId($id);
                    $review->setDetail($content);
                    $review->setEntityId(1);
                    $review->setStoreId(0);
                    $review->setStatusId($statusId);
                    $review->setCustomerId(null);
                    $review->setNickname($nickName);
                    $review->setStores($storeViews);
                    $review->setSkipCreatedAtSet(true);
                    $review->save();

                    $rating = Mage::getModel('rating/rating');
                    $rating->setRatingId($ratingId);
                    $rating->setReviewId($review->getId());
                    $rating->setCustomerId(null);
                    $rating->addOptionVote($options[$ratingVal], $productId);
                    $review->aggregate();
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'feedbackcompany.log');
            }
        }

        Mage::helper('feedbackcompany')->saveConfigValue(self::XML_LAST_RUN, now());

        return array(
            'status'  => 'OK',
            'new'     => $this->new,
            'company' => $feed['feed']['shop']['webshop_name']
        );
    }

    /**
     * @param $storeId
     * @param $type
     *
     * @return array
     */
    public function getFeed($storeId, $type)
    {
        $apiUrl = self:: FBC_REVIEW_URL;

        if ($type == 'full') {
            $apiUrl = self:: FBC_REVIEW_URL_FULL;
        }

        $apiResult = $this->makeRequest($apiUrl, $storeId);

        if ($apiResult) {
            if (!empty($apiResult['message']) && $apiResult['message'] == 'OK') {
                return array(
                    'status' => 'OK',
                    'feed'   => $apiResult['data'][0]
                );
            }
            return array(
                'status' => 'ERROR',
                'error'  => isset($apiResult['error']) ? $apiResult['error'] : ''
            );
        } else {
            return array(
                'status' => 'ERROR',
                'error'  => Mage::helper('feedbackcompany')->__('Error connect to the API.')
            );
        }
    }

    /**
     * @param $ratingId
     *
     * @return array
     */
    public function getRatingOptionArray($ratingId)
    {
        $options = Mage::getModel('rating/rating_option')->getCollection()
            ->addFieldToFilter('rating_id', $ratingId);
        $array = array();
        foreach ($options as $option) {
            $array[$option['value']] = $option['option_id'];
        }

        return $array;
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getAllStoreViews($storeId)
    {
        $fbcReviewstores = array();
        $clientId = Mage::getStoreConfig('feedbackcompany/general/client_id', $storeId);
        $stores = Mage::getModel('core/store')->getCollection();
        foreach ($stores as $store) {
            if ($store->getIsActive()) {
                if (Mage::getStoreConfig('feedbackcompany/productreviews/enabled', $store->getId())) {
                    $cId = Mage::getStoreConfig('feedbackcompany/general/client_id', $store->getId());
                    if ($clientId == $cId) {
                        $fbcReviewstores[] = $store->getId();
                    }
                }
            }
        }

        return $fbcReviewstores;
    }

    /**
     * @param $feedbackId
     *
     * @return bool|mixed
     */
    public function getReviewIdByFeedbackId($feedbackId)
    {
        $loadedReview = Mage::getModel('review/review')
            ->load($feedbackId, 'feedbackcompany_id');

        if (!empty($loadedReview)) {
            return $loadedReview->getId();
        }

        return false;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function getFirstSentence($string)
    {
        $string = str_replace(" .", ".", $string);
        $string = str_replace(" ?", "?", $string);
        $string = str_replace(" !", "!", $string);
        preg_match('/^.*[^\s](\.|\?|\!)/U', $string, $match);
        if (!empty($match[0])) {
            return $match[0];
        } else {
            return Mage::helper('core/string')->truncate($string, 50) . '...';
        }
    }

    /**
     * @param $date
     *
     * @return string
     */
    public function reformatDate($date)
    {
        $this->new++;
        $datetime = DateTime::createFromFormat('F, j Y H:i:s T', $date);
        if ($datetime) {
            return $datetime->format('Y-m-d H:i:s');
        }
    }

}