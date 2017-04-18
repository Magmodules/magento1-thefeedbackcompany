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

class Magmodules_Feedbackcompany_Model_Stats extends Magmodules_Feedbackcompany_Model_Api
{

    const FBC_REVIEW_SUMMARY_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/summary';

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/stats');
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function runUpdate($storeId)
    {
        $feed = $this->getFeed($storeId);

        if ($feed['status'] != 'OK') {
            return $feed;
        }

        $id = $this->getShopIdByFeedbackId($feed['feed']['shop']['id']);
        $this->saveSummary(
            array(
                'id'         => $id,
                'company'    => $feed['feed']['shop']['webshop_name'],
                'shop_id'    => $feed['feed']['shop']['id'],
                'score'      => ($feed['feed']['review_summary']['merchant_score'] * 10),
                'scoremax'   => ($feed['feed']['review_summary']['max_score'] * 10),
                'votes'      => $feed['feed']['review_summary']['total_reviews'],
                'review_url' => $feed['feed']['shop']['review_url'],
                'recommends' => $feed['feed']['review_summary']['total_recommends'],
                'client_id'  => $this->getClientId($storeId)
            )
        );

        return array(
            'status'  => 'OK',
            'company' => $feed['feed']['shop']['webshop_name']
        );
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getFeed($storeId)
    {
        $apiResult = $this->makeRequest(self:: FBC_REVIEW_SUMMARY_URL, $storeId);

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
                'error'  => Mage::helper('feedbackcompany')->__('Error connect to the API.'),
            );
        }
    }

    /**
     * @param $feedbackId
     *
     * @return mixed
     */
    public function getShopIdByFeedbackId($feedbackId)
    {
        $id = $this->getCollection()
            ->addFieldToFilter('shop_id', $feedbackId)
            ->addFieldToSelect('id')
            ->getFirstItem()
            ->getId();

        return $id;
    }

    /**
     * @param $data
     */
    public function saveSummary($data)
    {
        Mage::getModel('feedbackcompany/stats')->setData($data)->save();
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getShopIdByStoreId($storeId = null)
    {
        if (empty($storeId)) {
            $storeId = Mage::app()->getStore()->getStoreId();
        }

        if($stats = $this->loadByStoreId($storeId)) {
            return $stats->getShopId();
        }

        return false;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function loadByStoreId($storeId)
    {
        $clientId = Mage::getStoreConfig('feedbackcompany/general/client_id', $storeId);
        $stats = $this->getCollection()->addFieldToFilter('client_id', $clientId)->getFirstItem();

        if ($stats->getScore() > 0) {
            $stats->setPercentage($stats->getScore());
            $stats->setStarsQty(number_format(($stats->getScore() / 10), 1, ',', ''));
            return $stats;
        } else {
            return false;
        }
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getFeedbackUrl($storeId = null)
    {
        if (empty($storeId)) {
            $storeId = Mage::app()->getStore()->getStoreId();
        }

        $clientId = Mage::getStoreConfig('feedbackcompany/general/client_id', $storeId);
        $stats = $this->getCollection()->addFieldToFilter('client_id', $clientId)->getFirstItem();

        if($stats) {
            return $stats->getReviewUrl();
        }
    }

}