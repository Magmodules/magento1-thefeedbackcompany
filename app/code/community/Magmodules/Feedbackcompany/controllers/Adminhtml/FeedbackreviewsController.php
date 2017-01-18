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
class Magmodules_Feedbackcompany_Adminhtml_FeedbackreviewsController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('feedbackcompany/feedbackreviews')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Items Manager'),
                Mage::helper('adminhtml')->__('Item Manager')
            );

        return $this;
    }

    /**
     *
     */
    public function processAction()
    {
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds();
        $startTime = microtime(true);
        foreach ($storeIds as $storeId) {
            $msg = '';
            $apiId = Mage::getStoreConfig('feedbackcompany/general/api_id', $storeId);
            $result = Mage::getModel('feedbackcompany/api')->processFeed($storeId, 'all');
            $time = (microtime(true) - $startTime);

            Mage::getModel('feedbackcompany/log')->addToLog('reviews', $storeId, $result, '', $time, '', '');

            $status = $result['stats']['status'];
            if (($result['review_new'] > 0) || ($result['review_updates'] > 0) || ($status == 'success')) {
                $msg = Mage::helper('feedbackcompany')->__('Webwinkel ID %s:', $apiId) . ' ';
                $msg .= Mage::helper('feedbackcompany')->__('%s new review(s)', $result['review_new']) . ', ';
                $msg .= Mage::helper('feedbackcompany')->__('%s review(s) updated', $result['review_updates']) . ' & ';
                $msg .= Mage::helper('feedbackcompany')->__('and total score updated.');
            }

            if ($msg) {
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } else {
                if (!empty($result['stats']['msg'])) {
                    $msg = Mage::helper('feedbackcompany')->__('Webwinkel ID %s: %s', $apiId, $result['stats']['msg']);
                    Mage::getSingleton('adminhtml/session')->addError($msg);
                } else {
                    $msg = $this->__('Webwinkel ID %s: no updates found, feed is empty or not found!', $apiId);
                    Mage::getSingleton('adminhtml/session')->addError($msg);
                }
            }
        }

        Mage::getModel('feedbackcompany/stats')->processOverall();
        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('adminhtml/system_config/edit/section/feedbackcompany');
    }

    /**
     *
     */
    public function massDisableAction()
    {
        $reviewIds = $this->getRequest()->getParam('reviewids');
        if (!is_array($reviewIds)) {
            $msg = Mage::helper('feedbackcompany')->__('Please select item(s)');
            Mage::getSingleton('adminhtml/session')->addError($msg);
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    $reviews = Mage::getModel('feedbackcompany/reviews')->load($reviewId);
                    $reviews->setStatus(0)->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('feedbackcompany')->__('Total of %d review(s) were disabled.', count($reviewIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function massEnableAction()
    {
        $reviewIds = $this->getRequest()->getParam('reviewids');
        if (!is_array($reviewIds)) {
            $msg = Mage::helper('feedbackcompany')->__('Please select item(s)');
            Mage::getSingleton('adminhtml/session')->addError($msg);
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    $reviews = Mage::getModel('feedbackcompany/reviews')->load($reviewId);
                    $reviews->setStatus(1)->save();
                }

                $msg = $this->__('Total of %d review(s) were enabled.', count($reviewIds));
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function massEnableSidebarAction()
    {
        $reviewIds = $this->getRequest()->getParam('reviewids');
        if (!is_array($reviewIds)) {
            $msg = Mage::helper('feedbackcompany')->__('Please select item(s)');
            Mage::getSingleton('adminhtml/session')->addError($msg);
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    $reviews = Mage::getModel('feedbackcompany/reviews')->load($reviewId);
                    $reviews->setSidebar(1)->save();
                }

                $msg = $this->__('Total of %d review(s) were added to the sidebar.', count($reviewIds));
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function massDisableSidebarAction()
    {
        $reviewIds = $this->getRequest()->getParam('reviewids');
        if (!is_array($reviewIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    $reviews = Mage::getModel('feedbackcompany/reviews')->load($reviewId);
                    $reviews->setSidebar(0)->save();
                }

                $msg = $this->__('Total of %d review(s) were removed from the sidebar.', count($reviewIds));
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function truncateAction()
    {
        $i = 0;
        $collection = Mage::getModel('feedbackcompany/reviews')->getCollection();
        foreach ($collection as $item) {
            $item->delete();
            $i++;
        }

        $msg = $this->__('Succefully deleted all %s saved review(s).', $i);

        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        Mage::getModel('feedbackcompany/reviews')->flushCache();
        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function productreviewsAction()
    {
        $storeIds = Mage::getModel('feedbackcompany/api')->getStoreIds('oauth');
        $startTime = microtime(true);
        $qty = 0;
        $errors = array();
        foreach ($storeIds as $storeId) {
            $enabled = Mage::getStoreConfig('feedbackcompany/general/enabled', $storeId);
            $reviewsEnabled = Mage::getStoreConfig('feedbackcompany/productreviews/enabled', $storeId);
            $clientId = Mage::getStoreConfig('feedbackcompany/productreviews/client_id', $storeId);
            $clientSecret = Mage::getStoreConfig('feedbackcompany/productreviews/client_secret', $storeId);
            if ($enabled && $reviewsEnabled && !empty($clientId) && !empty($clientSecret)) {
                $feed = Mage::getModel('feedbackcompany/api')->getFeed($storeId, 'productreviews', 'last_month');
                if ($feed['status'] == 'OK') {
                    $results = Mage::getModel('feedbackcompany/productreviews')->processFeed($feed, $storeId);
                    if ($results['review_new'] > 0) {
                        $qty = ($qty + $results['review_new']);
                        $log = Mage::getModel('feedbackcompany/log');
                        $log->addToLog('productreviews', $storeId, $results, '', (microtime(true) - $startTime), '');
                    }
                } else {
                    $errors[$clientId] = $feed['error'];
                }
            }
        }

        if (count($errors) > 0) {
            foreach ($errors as $key => $value) {
                $msg = $this->__('API Response for client ID: %s => %s', $key, $value);
                Mage::getSingleton('adminhtml/session')->addError($msg);
            }
        } else {
            if ($qty > 0) {
                $msg = $this->__('Imported %d new productreview(s).', $qty);
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } else {
                $msg = $this->__('No new reviews found.', $qty);
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            }
        }

        $this->_redirect('adminhtml/system_config/edit/section/feedbackcompany');
    }

    public function exportCsvAction()
    {
        $reviews = $this->getRequest()->getPost('reviews', array());
        $filter = $this->getRequest()->getParam('filter');
        $storeId = '';
        if ($filter) {
            parse_str(urldecode(base64_decode($filter)), $params);
            if (!empty($params['visible_in'])) {
                $storeId = $params['visible_in'];
            }
        }

        if (empty($storeId) && (!Mage::app()->isSingleStoreMode())) {
            $msg = $this->__('Please select specific storeview in the grid before exporting the reviews.');
            Mage::getSingleton('adminhtml/session')->addError($msg);
            $this->_redirect('adminhtml/catalog_product_review');
        } else {
            $store = Mage::getModel('core/store')->load($storeId);
            if ($csvData = Mage::getModel('feedbackcompany/export')->getFeed($reviews, $storeId)) {
                $fileName = 'product-reviews-' . strtolower($store->getName()) . '.csv';
                $path = Mage::getBaseDir('var') . DS . 'export';
                if (!is_dir($path)) {
                    mkdir($path);
                }

                $file = $path . DS . $fileName;
                $csv = new Varien_File_Csv();
                $csv->saveData($file, $csvData);
                $this->_prepareDownloadResponse($fileName, array('type' => 'filename', 'value' => $file));
            } else {
                $msg = $this->__('Error, could not export the csv file.');

                Mage::getSingleton('adminhtml/session')->addError($msg);
                $this->_redirect('adminhtml/catalog_product_review');
            }
        }
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('shopreview/feedbackcompany/feedbackcompany_reviews');
    }

}