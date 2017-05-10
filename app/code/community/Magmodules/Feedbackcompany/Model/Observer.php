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

class Magmodules_Feedbackcompany_Model_Observer
{

    /**
     * Stats cron
     */
    public function processStats()
    {
        $logModel = Mage::getModel('feedbackcompany/log');
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds('cron');
        foreach ($storeIds as $storeId) {
            $startTime = microtime(true);
            $results = Mage::getModel('feedbackcompany/stats')->runUpdate($storeId);
            $logModel->addToLog('reviews', $storeId, $results, '', $startTime, 'stats');
        }
    }

    /**
     * Reviews cron
     */
    public function processReviews()
    {
        $logModel = Mage::getModel('feedbackcompany/log');
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds('cron');
        foreach ($storeIds as $storeId) {
            $startTime = microtime(true);
            $results = Mage::getModel('feedbackcompany/reviews')->runUpdate($storeId, 'last_week');
            $logModel->addToLog('reviews', $storeId, $results, '', $startTime, 'reviews');
        }
    }

    /**
     * History cron
     */
    public function processHistory()
    {
        $logModel = Mage::getModel('feedbackcompany/log');
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds('cron');
        foreach ($storeIds as $storeId) {
            $startTime = microtime(true);
            $results = Mage::getModel('feedbackcompany/reviews')->runUpdate($storeId, 'full');
            $logModel->addToLog('reviews', $storeId, $results, '', $startTime, 'reviews');
        }
    }

    /**
     * Productreviews cron
     */
    public function processProductreviews()
    {
        $logModel = Mage::getModel('feedbackcompany/log');
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds('prcron');
        foreach ($storeIds as $storeId) {
            $startTime = microtime(true);
            $results = Mage::getModel('feedbackcompany/productreviews')->runUpdate($storeId, 'last_week');
            $logModel->addToLog('productreviews', $storeId, $results, '', $startTime, 'productreviews');
        }
    }

    /**
     * Log cleaning cron
     */
    public function cleanLog()
    {
        $enabled = Mage::getStoreConfig('feedbackcompany/log/clean', 0);
        $days = Mage::getStoreConfig('feedbackcompany/log/clean_days', 0);
        if (($enabled) && ($days > 0)) {
            $deldate = date('Y-m-d', strtotime('-' . $days . ' days'));
            $logs = Mage::getModel('feedbackcompany/log')->getCollection()
                ->addFieldToSelect('id')
                ->addFieldToFilter('date', array('lteq' => $deldate));
            foreach ($logs as $log) {
                $log->delete();
            }
        }
    }

    /**
     * sales_order_shipment_save_after observer for invitation call
     *
     * @param $observer
     */
    public function processFeedbackInvitationcallAfterShipment($observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $invitationEnabled = Mage::getStoreConfig('feedbackcompany/invitation/enabled', $order->getStoreId());
        $connector = Mage::getStoreConfig('feedbackcompany/invitation/connector', $order->getStoreId());
        if ($invitationEnabled && $connector) {
            $status = Mage::getStoreConfig('feedbackcompany/invitation/status', $order->getStoreId());
            if ($order->getStatus() == $status) {
                if (!$order->getFeedbackSent()) {
                    $backlog = Mage::getStoreConfig('feedbackcompany/invitation/backlog', $order->getStoreId());
                    if ($backlog > 0) {
                        $dateDiff = floor(time() - strtotime($order->getCreatedAt())) / (60 * 60 * 24);
                        if ($dateDiff < $backlog) {
                            Mage::getModel('feedbackcompany/api')->sendInvitation($order);
                        }
                    } else {
                        Mage::getModel('feedbackcompany/api')->sendInvitation($order);
                    }
                }
            }
        }
    }

    /**
     * sales_order_save_commit_after observer for invitation call
     *
     * @param $observer
     */
    public function processFeedbackInvitationcall($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $invitationEnabled = Mage::getStoreConfig('feedbackcompany/invitation/enabled', $order->getStoreId());
        $connector = Mage::getStoreConfig('feedbackcompany/invitation/connector', $order->getStoreId());
        if ($invitationEnabled && $connector) {
            $status = Mage::getStoreConfig('feedbackcompany/invitation/status', $order->getStoreId());
            if ($order->getStatus() == $status) {
                if (!$order->getFeedbackSent()) {
                    $backlog = Mage::getStoreConfig('feedbackcompany/invitation/backlog', $order->getStoreId());
                    if ($backlog > 0) {
                        $dateDiff = floor(time() - strtotime($order->getCreatedAt())) / (60 * 60 * 24);
                        if ($dateDiff < $backlog) {
                            Mage::getModel('feedbackcompany/api')->sendInvitation($order);
                        }
                    } else {
                        Mage::getModel('feedbackcompany/api')->sendInvitation($order);
                    }
                }
            }
        }
    }

    /**
     * Add export option to review grid
     *
     * @param $observer
     */
    public function addExportOption($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $targetBlock = 'Mage_Adminhtml_Block_Widget_Grid_Massaction';
        $controllerName = 'catalog_product_review';
        if (get_class($block) == $targetBlock && $block->getRequest()->getControllerName() == $controllerName) {
            $request = Mage::app()->getFrontController()->getRequest();
            $filter = $request->getParam('filter');
            $block->addItem(
                'reviewsexport',
                array(
                    'label' => Mage::helper('feedbackcompany')->__('Export Reviews'),
                    'url'   => Mage::app()->getStore()->getUrl('*/feedbackreviews/exportcsv/filter/' . $filter),
                )
            );
        }
    }

}