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

class Magmodules_Feedbackcompany_Block_Sidebar extends Mage_Core_Block_Template
{

    /**
     * @param $sidebar
     * @return bool
     */
    public function getSidebarCollection($sidebar)
    {
        return Mage::helper('feedbackcompany')->getSidebarCollection($sidebar);
    }

    /**
     * @param $review
     * @param string $sidebar
     * @return mixed
     */
    public function formatContent($review, $sidebar = 'left')
    {
        return Mage::helper('feedbackcompany')->formatContent($review, $sidebar);
    }

    /**
     * @param string $sidebar
     * @return bool|string
     */
    public function getReviewsUrl($sidebar = 'left')
    {
        if($url = Mage::helper('feedbackcompany')->getReviewsUrl($sidebar)) {
            return '<a href="' . $url . '" target="_blank">' . $this->__('View all reviews') . '</a>';
        }

        return false;
    }

    /**
     * @param string $sidebar
     * @return bool
     */
    public function getSnippetsEnabled($sidebar = 'left')
    {
        return Mage::helper('feedbackcompany')->getSnippetsEnabled($sidebar);
    }

    /**
     * @return mixed
     */
    public function getTotalScore()
    {
        return $this->helper('feedbackcompany')->getTotalScore();
    }

    /**
     * @param $votes
     *
     * @return string
     */
    public function getVotesHtml($votes)
    {
        return $this->__('Based on %s reviews', '<span itemprop="ratingCount">' .$votes . '</span>');
    }

    /**
     * @return string
     */
    public function getLogoHtml()
    {
        $img = $this->getSkinUrl('magmodules/feedbackcompany/images/logo.png');
        return '<img src="' . $img .'" class="feedbackcompany-logo">';
    }

}