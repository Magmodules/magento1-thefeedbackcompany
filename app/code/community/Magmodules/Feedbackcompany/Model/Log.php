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
class Magmodules_Feedbackcompany_Model_Log extends Mage_Core_Model_Abstract
{

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/log');
    }

    /**
     * @param        $type
     * @param        $storeId
     * @param string $review
     * @param string $response
     * @param        $time
     * @param string $crontype
     * @param string $apiUrl
     * @param string $orderId
     */
    public function addToLog(
        $type,
        $storeId,
        $review = '',
        $response = '',
        $time,
        $crontype = '',
        $apiUrl = '',
        $orderId = ''
    )
    {
        if (Mage::getStoreConfig('feedbackcompany/log/enabled')) {
            if ($type == 'productreview') {
                $apiId = Mage::getStoreConfig('feedbackcompany/productreviews/client_token', $storeId);
                $apiUrl = Mage::getStoreConfig('feedbackcompany/productreviews/client_token', $storeId);
            } else {
                $apiId = Mage::getStoreConfig('feedbackcompany/general/api_id', $storeId);
            }

            $company = Mage::getStoreConfig('feedbackcompany/general/company', $storeId);
            $reviewUpdates = '';
            $reviewNew = '';

            if ($review) {
                if (!empty($review['review_updates'])) {
                    $reviewUpdates = $review['review_updates'];
                }

                if (!empty($review['review_new'])) {
                    $reviewNew = $review['review_new'];
                }

                if (!empty($review['stats']['msg'])) {
                    $response = $review['stats']['msg'];
                }
            }

            $logModel = Mage::getModel('feedbackcompany/log');
            $logModel->setType($type)
                ->setShopId($apiId)
                ->setStoreId($storeId)
                ->setCompany($company)
                ->setReviewUpdate($reviewUpdates)
                ->setReviewNew($reviewNew)
                ->setResponse($response)
                ->setOrderId($orderId)
                ->setCron($crontype)
                ->setDate(now())
                ->setTime($time)
                ->setApiUrl($apiUrl)
                ->save();
        }
    }

}