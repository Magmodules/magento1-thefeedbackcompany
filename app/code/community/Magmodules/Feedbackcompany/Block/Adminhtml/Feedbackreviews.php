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
class Magmodules_Feedbackcompany_Block_Adminhtml_Feedbackreviews extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Magmodules_Feedbackcompany_Block_Adminhtml_Feedbackreviews constructor.
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_feedbackreviews';
        $this->_blockGroup = 'feedbackcompany';
        $this->_headerText = Mage::helper('feedbackcompany')->__('The Feedback Company - Reviews');

        parent::__construct();

        $this->_removeButton('add');
        $this->_addButton(
            'module_controller', array(
                'label' => Mage::helper('feedbackcompany')->__('Delete all reviews'),
                'onclick' => "setLocation('{$this->getUrl('adminhtml/feedbackreviews/truncate')}')",
                'confirm' => Mage::helper('feedbackcompany')->__('Are you sure you want to delete all reviews?'),
            )
        );
    }

}