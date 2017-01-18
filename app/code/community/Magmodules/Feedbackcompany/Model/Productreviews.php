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
class Magmodules_Feedbackcompany_Model_Productreviews extends Mage_Core_Model_Abstract
{

    /**
     * @param $feed
     * @param int $storeId
     *
     * @return array
     */
    public function processFeed($feed, $storeId = 0)
    {
        $new = 0;
        $updates = 0;
        $feed = $feed['feed'];
        $statusId = Mage::getStoreConfig('feedbackcompany/productreviews/review_import_status', $storeId);
        $ratingId = Mage::getStoreConfig('feedbackcompany/productreviews/review_import_rating', $storeId);
        $options = $this->getRatingOptionArray($ratingId);

        foreach ($feed->product_reviews as $fbcReview) {
            $feedbackId = $fbcReview->product_opinion_id;
            $loadedReview = Mage::getModel('review/review')->load($feedbackId, 'feedbackcompany_id');

            if (($loadedReview->getReviewId() < 1) && ($fbcReview->rating > 0)) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $fbcReview->product_sku);
                if (!$product) {
                    continue;
                }

                $content = $fbcReview->review;
                if (!empty($content) && ($fbcReview->rating > 0) && (!empty($options[$fbcReview->rating]))) {
                    try {
                        $title = $this->getFirstSentence($content);
                        if (!empty($title) > 0) {
                            $dateCreated = Mage::getModel('core/date')->timestamp($fbcReview->date_created);
                            $createdAt = date('Y-m-d H:i:s', $dateCreated);
                            $review = Mage::getModel('review/review');
                            $review->setEntityPkValue($product->getId());
                            $review->setCreatedAt($createdAt);
                            $review->setTitle($title);
                            $review->setFeedbackcompanyId($feedbackId);
                            $review->setDetail($content);
                            $review->setEntityId(1);
                            $review->setStoreId(0);
                            $review->setStatusId($statusId);
                            $review->setCustomerId(null);
                            $review->setNickname($fbcReview->client->name);
                            $review->setStores($this->getAllStoreViews($storeId));
                            $review->setSkipCreatedAtSet(true);
                            $review->save();

                            $rating = Mage::getModel('rating/rating');
                            $rating->setRatingId($ratingId);
                            $rating->setReviewId($review->getId());
                            $rating->setCustomerId(null);
                            $rating->addOptionVote($options[$fbcReview->rating], $product->getId());
                            $review->aggregate();
                            $new++;
                        }
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, 'feedbackcompany.log');
                    }
                }
            }
        }

        $config = Mage::getModel('core/config');
        $config->saveConfig('feedbackcompany/productreviews/lastrun', now(), 'default', 0);

        $result = array();
        $result['review_updates'] = $updates;
        $result['review_new'] = $new;

        return $result;
    }

    /**
     * @param $ratingId
     * @return array
     */
    public function getRatingOptionArray($ratingId)
    {
        $options = Mage::getModel('rating/rating_option')->getCollection()->addFieldToFilter('rating_id', $ratingId);
        $array = array();
        foreach ($options as $option) {
            $array[$option['value']] = $option['option_id'];
        }

        return $array;
    }

    /**
     * @param $string
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
     * @param $storeId
     *
     * @return array
     */
    public function getAllStoreViews($storeId)
    {
        $clientId = Mage::getStoreConfig('feedbackcompany/productreviews/client_id', $storeId);
        $clientSecret = Mage::getStoreConfig('feedbackcompany/productreviews/client_secret', $storeId);
        $fbcReviewstores = array();
        $stores = Mage::getModel('core/store')->getCollection();
        foreach ($stores as $store) {
            if ($store->getIsActive()) {
                if (Mage::getStoreConfig('feedbackcompany/productreviews/enabled', $store->getId())) {
                    $cId = Mage::getStoreConfig('feedbackcompany/productreviews/client_id', $store->getId());
                    $cSecret = Mage::getStoreConfig('feedbackcompany/productreviews/client_secret', $store->getId());
                    if (($clientId == $cId) && ($clientSecret == $cSecret)) {
                        $fbcReviewstores[] = $store->getId();
                    }
                }
            }
        }

        return $fbcReviewstores;
    }

}