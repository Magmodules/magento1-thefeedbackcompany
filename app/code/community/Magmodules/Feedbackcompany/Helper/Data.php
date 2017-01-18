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
class Magmodules_Feedbackcompany_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @return mixed
     */
    public function getTotalScore()
    {
        if (Mage::getStoreConfig('feedbackcompany/general/enabled')) {
            $shopId = Mage::getStoreConfig('feedbackcompany/general/api_id');
            $reviewStats = Mage::getModel('feedbackcompany/stats')->load($shopId, 'shop_id');
            if ($reviewStats->getScore() > 0) {
                $reviewStats->setPercentage($reviewStats->getScore());
                $reviewStats->setStarsQty(number_format(($reviewStats->getScore() / 10), 1, ',', ''));

                return $reviewStats;
            }
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getStyle($type = 'sidebar')
    {
        $style = '';

        if ($type == 'left') {
            $style = Mage::getStoreConfig('feedbackcompany/sidebar/left_style');
        }

        if ($type == 'right') {
            $style = Mage::getStoreConfig('feedbackcompany/sidebar/right_style');
        }

        if ($type == 'sidebar') {
            $style = Mage::getStoreConfig('feedbackcompany/block/sidebar_style');
        }

        return $style;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function getSnippetsEnabled($type = 'sidebar')
    {
        if (Mage::app()->getRequest()->getRouteName() == 'feedbackcompany') {
            return false;
        } else {
            $snippets = '';
            switch ($type) {
                case 'left':
                    $snippets = Mage::getStoreConfig('feedbackcompany/sidebar/left_snippets');
                    break;
                case 'right':
                    $snippets = Mage::getStoreConfig('feedbackcompany/sidebar/right_snippets');
                    break;
                case 'sidebar':
                    $snippets = Mage::getStoreConfig('feedbackcompany/block/sidebar_snippets');
                    break;
                case 'small':
                    $snippets = Mage::getStoreConfig('feedbackcompany/block/small_snippets');
                    break;
                case 'header':
                    $snippets = Mage::getStoreConfig('feedbackcompany/block/header_snippets');
                    break;
                case 'medium':
                    $snippets = Mage::getStoreConfig('feedbackcompany/block/medium_snippets');
                    break;
            }

            return $snippets;
        }
    }

    /**
     * @param $sidebar
     *
     * @return bool
     */
    public function getSidebarCollection($sidebar)
    {
        $enabled = '';
        $qty = '5';
        if (Mage::getStoreConfig('feedbackcompany/general/enabled')) {
            if ($sidebar == 'left') {
                $qty = Mage::getStoreConfig('feedbackcompany/sidebar/left_qty');
                $enabled = Mage::getStoreConfig('feedbackcompany/sidebar/left');
            }

            if ($sidebar == 'right') {
                $qty = Mage::getStoreConfig('feedbackcompany/sidebar/right_qty');
                $enabled = Mage::getStoreConfig('feedbackcompany/sidebar/right');
            }

            if ($sidebar == 'sidebar') {
                $qty = Mage::getStoreConfig('feedbackcompany/block/sidebar_qty');
                $enabled = Mage::getStoreConfig('feedbackcompany/block/sidebar');
            }
        }

        if ($enabled) {
            $shopId = Mage::getStoreConfig('feedbackcompany/general/api_id');
            $reviews = Mage::getModel("feedbackcompany/reviews")->getCollection();
            $reviews->setOrder('date_created', 'DESC');
            $reviews->addFieldToFilter('status', 1);
            $reviews->addFieldToFilter('sidebar', 1);
            $reviews->addFieldToFilter('shop_id', array('eq' => array($shopId)));
            $reviews->setPageSize($qty);
            $reviews->load();

            return $reviews;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getLatestReview()
    {
        if (Mage::getStoreConfig('feedbackcompany/block/medium_review')) {
            $shopId = Mage::getStoreConfig('feedbackcompany/general/api_id');
            $review = Mage::getModel("feedbackcompany/reviews")->getCollection();
            $review->setOrder('date_created', 'DESC');
            $review->addFieldToFilter('status', 1);
            $review->addFieldToFilter('review_text', array('notnull' => true));
            $review->addFieldToFilter('shop_id', array('eq' => array($shopId)));
            $review->setPageSize(1);

            return $review->getFirstItem();
        }

        return false;
    }

    /**
     * @param        $sidebarreview
     * @param string $sidebar
     *
     * @return string
     */
    public function formatContent($sidebarreview, $sidebar = 'left')
    {
        $content = $sidebarreview->getReviewText();
        $charLimit = '';
        if ($sidebar == 'left') {
            $charLimit = Mage::getStoreConfig('feedbackcompany/sidebar/left_lenght');
        }

        if ($sidebar == 'right') {
            $charLimit = Mage::getStoreConfig('feedbackcompany/sidebar/right_lenght');
        }

        if ($sidebar == 'sidebar') {
            $charLimit = Mage::getStoreConfig('feedbackcompany/block/sidebar_lenght');
        }

        if ($sidebar == 'medium') {
            $charLimit = Mage::getStoreConfig('feedbackcompany/block/medium_lenght');
        }

        if ($charLimit > 1) {
            $url = $this->getReviewsUrl($sidebar);
            $content = Mage::helper('core/string')->truncate($content, $charLimit, ' ...');
            if ($url) {
                $content .= ' <a href="' . $url . '" target="_blank">' . $this->__('Read More') . '</a>';
            }
        }

        return $content;
    }

    /**
     * @param $type
     *
     * @return bool|string
     */
    public function getReviewsUrl($type)
    {
        $link = '';
        switch ($type) {
            case 'left':
                $link = Mage::getStoreConfig('feedbackcompany/sidebar/left_link');
                break;
            case 'right':
                $link = Mage::getStoreConfig('feedbackcompany/sidebar/right_link');
                break;
            case 'sidebar':
                $link = Mage::getStoreConfig('feedbackcompany/block/sidebar_link');
                break;
            case 'small':
                $link = Mage::getStoreConfig('feedbackcompany/block/small_link');
                break;
            case 'header':
                $link = Mage::getStoreConfig('feedbackcompany/block/header_link');
                break;
            case 'medium':
                $link = Mage::getStoreConfig('feedbackcompany/block/medium_link');
                break;
        }

        if ($link == 'internal') {
            return Mage::getBaseUrl() . 'feedbackcompany';
        }

        if ($link == 'external') {
            return Mage::getStoreConfig('feedbackcompany/general/url');
        }

        return false;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    public function getBlockEnabled($type)
    {
        if (Mage::getStoreConfig('feedbackcompany/general/enabled')) {
            $enabled = '';
            switch ($type) {
                case 'left':
                    $enabled = Mage::getStoreConfig('feedbackcompany/sidebar/left');
                    break;
                case 'right':
                    $enabled = Mage::getStoreConfig('feedbackcompany/sidebar/right');
                    break;
                case 'sidebar':
                    $enabled = Mage::getStoreConfig('feedbackcompany/block/sidebar');
                    break;
                case 'small':
                    $enabled = Mage::getStoreConfig('feedbackcompany/block/small');
                    break;
                case 'header':
                    $enabled = Mage::getStoreConfig('feedbackcompany/block/header');
                    break;
                case 'medium':
                    $enabled = Mage::getStoreConfig('feedbackcompany/block/medium');
                    break;
            }

            return $enabled;
        }

        return false;
    }

    /**
     * @param        $rating
     * @param string $type
     *
     * @return bool|string
     */
    public function getHtmlStars($rating, $type = 'small')
    {
        $perc = $rating;
        $show = '';
        if ($type == 'small') {
            $show = Mage::getStoreConfig('feedbackcompany/block/small_stars');
        }

        if ($type == 'medium') {
            $show = Mage::getStoreConfig('feedbackcompany/block/medium_stars');
        }

        if ($show) {
            $html = '<div class="rating-box">';
            $html .= '	<div class="rating" style="width:' . $perc . '%"></div>';
            $html .= '</div>';

            return $html;
        }

        return false;
    }

    /**
     * @param $review
     *
     * @return array
     */
    public function formatScoresReview($review)
    {
        $scoreValues = array();
        $scoreValuesPossible = array(
            'aftersales' => 'Aftersales',
            'checkout' => 'Checkout',
            'information' => 'Information',
            'friendly' => 'Friendlyness',
            'leadtime' => 'Leadtime',
            'responsetime' => 'Responsetime',
            'order' => 'Orderprocess'
        );

        foreach ($scoreValuesPossible as $key => $value) {
            if ($review->getData("score_" . $key) > 0) {
                $scoreValues[$value] = $review->getData("score_" . $key) * 20;
            }
        }

        return $scoreValues;
    }

    /**
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getUncachedConfigValue($path, $storeId = 0)
    {
        $collection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', $path);
        if ($storeId == 0) {
            $collection = $collection->addFieldToFilter('scope_id', 0)->addFieldToFilter('scope', 'default');
        } else {
            $collection = $collection->addFieldToFilter('scope_id', $storeId)->addFieldToFilter('scope', 'stores');
        }

        return $collection->getFirstItem()->getValue();
    }

}