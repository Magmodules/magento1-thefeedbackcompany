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

class Magmodules_Feedbackcompany_Model_Reviews extends Magmodules_Feedbackcompany_Model_Api
{

    const CACHE_TAG = 'feedback_block';
    const XML_LAST_RUN = 'feedbackcompany/reviews/lastrun';
    const XML_FLUSH_CACHE = 'feedbackcompany/reviews/flushcache';
    const FBC_REVIEW_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/getrecent/?interval=last_week';
    const FBC_REVIEW_URL_FULL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/all/';

    public $new = 0;
    public $update = 0;

    /**
     * Reviews Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/reviews');
    }

    /**
     * @param $storeId
     * @param $type
     *
     * @return array
     */
    public function runUpdate($storeId, $type)
    {
        $feed = $this->getFeed($storeId, $type);

        if ($feed['status'] != 'OK') {
            return $feed;
        }

        if (!isset($feed['feed']['reviews'])) {
            return array();
        }

        foreach ($feed['feed']['reviews'] as $review) {
            $reviewId = $this->getReviewIdByFeedbackId($review['id']);
            if (!empty($reviewId) && ($type != 'full')) {
                continue;
            }

            $this->saveReview(
                array(
                    'review_id'          => $reviewId,
                    'feedback_id'        => $review['id'],
                    'company'            => $feed['feed']['shop']['webshop_name'],
                    'shop_id'            => $feed['feed']['shop']['id'],
                    'score'              => $review['total_score'],
                    'buy_online'         => $review['buy_online'],
                    'customer_recommend' => $this->getRecommends($review['recommends']),
                    'review_text'        => $this->getReviewTextFromQuestions($review['questions']),
                    'customer_name'      => $review['client']['name'],
                    'customer_sex'       => $review['client']['gender'],
                    'customer_age'       => $review['client']['age'],
                    'customer_city'      => $review['client']['city'],
                    'customer_email'     => $review['client']['email'],
                    'customer_country'   => $review['client']['country'],
                    'shop_comment'       => $review['shop_comment'],
                    'product'            => $review['product'],
                    'questions'          => json_encode($review['questions']),
                    'date_created'       => $this->reformatDate($review['date_created']),
                    'date_updated'       => $this->reformatDate($review['date_updated']),
                )
            );
        }

        Mage::helper('feedbackcompany')->saveConfigValue(self::XML_LAST_RUN, now());

        return array(
            'status'  => 'OK',
            'update'  => $this->update,
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
     * @param $feedbackId
     *
     * @return mixed
     */
    public function getReviewIdByFeedbackId($feedbackId)
    {
        $reviewId = $this->getCollection()
            ->addFieldToFilter('feedback_id', $feedbackId)
            ->addFieldToSelect('review_id')
            ->getFirstItem()
            ->getReviewId();

        return $reviewId;
    }

    /**
     * @param $data
     */
    public function saveReview($data)
    {
        Mage::getModel('feedbackcompany/reviews')->setData($data)->save();
        if ($data['review_id'] > 0) {
            $this->update++;
        } else {
            $this->new++;
        }
    }

    /**
     * @param $recommends
     *
     * @return int
     */
    public function getRecommends($recommends)
    {
        if (strtolower($recommends) == 'ja') {
            return 1;
        }

        if (strtolower($recommends) == 'nee') {
            return 0;
        }

        return -1;
    }

    /**
     * @param $questions
     *
     * @return mixed
     */
    public function getReviewTextFromQuestions($questions)
    {
        foreach ($questions as $question) {
            if ($question['type'] == 'main_open') {
                return $question['value'];
            }
        }

        return '';
    }

    /**
     * @param $date
     *
     * @return string
     */
    public function reformatDate($date)
    {
        $datetime = DateTime::createFromFormat('F, j Y H:i:s T', $date);
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Flush Cache function
     */
    public function flushCache()
    {
        if (Mage::getStoreConfig(self::XML_FLUSH_CACHE)) {
            Mage::app()->cleanCache(
                array(
                    Mage_Cms_Model_Block::CACHE_TAG,
                    Magmodules_Feedbackcompany_Model_Reviews::CACHE_TAG
                )
            );
        }
    }

}