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

class Magmodules_Feedbackcompany_Block_Custom extends Mage_Core_Block_Template
{

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $blockType = $this->getData("blocktype");
        $blockTypeTemplate = '';
        $total = $this->helper('feedbackcompany')->getTotalScore();

        if ($blockType == 'sidebar') {
            $enabled = $this->helper('feedbackcompany')->getBlockEnabled('sidebar');
            $sidebarreviews = $this->helper('feedbackcompany')->getSidebarCollection('sidebar');
            if ($total && $enabled && $sidebarreviews) {
                $this->setTotals($total);
                $this->setReviews($sidebarreviews);
                $blockTypeTemplate = 'magmodules/feedbackcompany/widget/sidebar.phtml';
            }
        }

        if ($blockType == 'medium') {
            $enabled = $this->helper('feedbackcompany')->getBlockEnabled('medium');
            if ($total && $enabled) {
                $this->setTotals($total);
                $blockTypeTemplate = 'magmodules/feedbackcompany/widget/medium.phtml';
            }
        }

        if ($blockType == 'small') {
            $enabled = $this->helper('feedbackcompany')->getBlockEnabled('small');
            if ($total && $enabled) {
                $this->setTotals($total);
                $blockTypeTemplate = 'magmodules/feedbackcompany/widget/small.phtml';
            }
        }

        if ($blockType == 'summary') {
            $enabled = $this->helper('feedbackcompany')->getBlockEnabled('summary');
            if ($total && $enabled) {
                $this->setTotals($total);
                $blockTypeTemplate = 'magmodules/feedbackcompany/widget/summary.phtml';
            }
        }

        if ($blockTypeTemplate) {
            $this->addData(
                array(
                    'cache_lifetime' => 7200,
                    'cache_tags' => array(
                        Mage_Cms_Model_Block::CACHE_TAG,
                        Magmodules_Feedbackcompany_Model_Reviews::CACHE_TAG
                    ),
                    'cache_key' => Mage::app()->getStore()->getStoreId() . '-' . $blockType . '-feedback-block',
                )
            );
            parent::_construct();
            $this->setTemplate($blockTypeTemplate);
        }
    }

    /**
     * @return mixed
     */
    public function getFeedbackcompanyData()
    {
        return $this->helper('feedbackcompany')->getTotalScore();
    }

    /**
     * @param $sidebarreview
     * @param $sidebar
     *
     * @return mixed
     */
    public function formatContent($sidebarreview, $sidebar)
    {
        return $this->helper('feedbackcompany')->formatContent($sidebarreview, $sidebar);
    }

    /**
     * @param $type
     *
     * @return mixed
     */
    public function getReviewsUrl($type)
    {
        return $this->helper('feedbackcompany')->getReviewsUrl($type);
    }

    /**
     * @param $sidebar
     *
     * @return mixed
     */
    public function getSnippetsEnabled($sidebar)
    {
        return $this->helper('feedbackcompany')->getSnippetsEnabled($sidebar);
    }

    /**
     * @return mixed
     */
    public function getLatestReview()
    {
        return $this->helper('feedbackcompany')->getLatestReview();
    }

    /**
     * @param $percentage
     * @param $type
     *
     * @return mixed
     */
    public function getHtmlStars($percentage, $type)
    {
        return $this->helper('feedbackcompany')->getHtmlStars($percentage, $type);
    }

}