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
class Magmodules_Feedbackcompany_Block_Adminhtml_Widget_Grid_Stars
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{

    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());

        if ($value == '0') {
            $output = '';
        } else {
            $output = '<span class="rating-empty"><span class="rating-star-' . $value . '"></span></span>';
            $extra = '';

            if ($row->getData('score_aftersales') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Aftersales:') . '</strong> ';
                $extra .= $row->getData('score_aftersales') . '/5<br>';
            }

            if ($row->getData('score_checkout') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Checkout process:') . '</strong> ';
                $extra .= $row->getData('score_checkout') . '/5<br>';
            }

            if ($row->getData('score_information' > 0)) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Information:') . '</strong> ';
                $extra .= $row->getData('score_information') . '/5<br>';
            }

            if ($row->getData('score_friendly') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Customer Friendlyness:') . '</strong> ';
                $extra .= $row->getData('score_friendly') . '/5<br>';
            }

            if ($row->getData('score_leadtime') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Leadtime:') . '</strong> ';
                $extra .= $row->getData('score_leadtime') . '/5<br>';
            }

            if ($row->getData('score_responsetime') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Repsonsetime:') . '</strong> ';
                $extra .= $row->getData('score_responsetime') . '/5<br>';
            }

            if ($row->getData('score_order') > 0) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Order process:') . '</strong> ';
                $extra .= $row->getData('score_order') . '/5<br>';
            }

            if (!empty($extra)) {
                $extra .= '<br/>';
            }

            if ($row->getData('text_positive')) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Strong:') . '</strong> ';
                $extra .= $row->getData('text_positive') . '<br>';
            }

            if ($row->getData('text_improvements')) {
                $extra .= '<strong>' . Mage::helper('feedbackcompany')->__('Can do better:') . '</strong> ';
                $extra .= $row->getData('text_improvements') . '<br>';
            }

            if (!empty($extra)) {
                $output .= '<a href="#" class="magtooltip" alt="">(i)<span>';
                $output .= $extra;
                $output .= '</span></a>';
            }
        }

        return $output;
    }

}