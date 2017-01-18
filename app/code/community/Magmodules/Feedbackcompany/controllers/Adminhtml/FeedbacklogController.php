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
class Magmodules_Feedbackcompany_Adminhtml_FeedbacklogController extends Mage_Adminhtml_Controller_Action
{

    /**
     *
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * @return $this
     */
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
    public function massDeleteAction()
    {
        $logIds = $this->getRequest()->getParam('logids');
        if (!is_array($logIds)) {
            $msg = Mage::helper('feedbackcompany')->__('Please select item(s)');
            Mage::getSingleton('adminhtml/session')->addError($msg);
        } else {
            try {
                foreach ($logIds as $id) {
                    Mage::getModel('feedbackcompany/log')->load($id)->delete();
                }

                $msg = Mage::helper('feedbackcompany')->__('Total of %d log record(s) deleted.', count($logIds));
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     *
     */
    public function cleanAction()
    {
        $logmodel = Mage::getModel('feedbackcompany/log');
        $logs = $logmodel->getCollection();
        foreach ($logs as $log) {
            $logmodel->load($log->getId())->delete();
        }

        $msg = Mage::helper('feedbackcompany')->__('Total of %s log record(s) deleted.', count($logs));
        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        $this->_redirect('*/*/index');
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('shopreview/feedbackcompany/feedbackcompany_log');
    }

}