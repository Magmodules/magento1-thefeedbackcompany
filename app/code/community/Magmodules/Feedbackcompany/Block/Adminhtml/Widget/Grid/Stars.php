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
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if ($row->getScore() == '0') {
            return '';
        }

        $output = '<span class="rating-empty"><span class="rating-star-' . $row->getScore() . '"></span></span>';

        $extra = '';
        if ($questions = $row->getQuestions()) {
            $questions = Mage::helper('feedbackcompany')->getQuestions($questions, false);
            foreach ($questions as $question) {
                $extra .= '<p><strong>' . $question['Reviewtitle'] . ':</strong> ';
                $extra .= $question['value_html'] . '</p>';
            }
        }

        if (!empty($extra)) {
            $output .= '<a href="#" class="magtooltip" alt="">(i)<span>';
            $output .= $extra;
            $output .= '</span></a>';
        }

        return $output;
    }

}