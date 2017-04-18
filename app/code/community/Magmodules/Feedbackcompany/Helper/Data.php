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

    const XML_CLIENT_ID = 'feedbackcompany/general/client_id';
    const XML_MODULE_ENABLED = 'feedbackcompany/general/enabled';
    const XML_REVIEWS_CRON = 'feedbackcompany/reviews/cron';
    const XML_PRODUCT_REVIEWS_CRON = 'feedbackcompany/productreviews/cron';
    const XML_PRODUCT_REVIEWS_ENABLED = 'feedbackcompany/productreviews/enabled';

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function isEnabled($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_MODULE_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isPrEnabled($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PRODUCT_REVIEWS_ENABLED, $storeId);
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function isCronEnabled($storeId)
    {
        $enabled = $this->isEnabled($storeId);
        $cron = Mage::getStoreConfig(self::XML_REVIEWS_CRON, $storeId);

        if ($enabled && $cron) {
            return true;
        }

        return false;
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function isPrCronEnabled($storeId)
    {
        $enabled = $this->isEnabled($storeId);
        $prEnabled = $this->isPrEnabled($storeId);
        $cron = Mage::getStoreConfig(self::XML_PRODUCT_REVIEWS_CRON, $storeId);

        if ($enabled && $cron && $prEnabled) {
            return true;
        }

        return false;
    }

    /**
     * @param     $path
     * @param     $value
     * @param int $storeId
     */
    public function saveConfigValue($path, $value, $storeId = 0)
    {
        $config = Mage::getModel('core/config');
        if ($storeId == 0) {
            $config->saveConfig($path, $value, 'default', 0);
        } else {
            $config->saveConfig($path, $value, 'stores', $storeId);
        }
    }

    /**
     * @return mixed
     */
    public function getTotalScore()
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        return Mage::getModel("feedbackcompany/stats")->loadByStoreId($storeId);
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
            $shopId = Mage::getModel("feedbackcompany/stats")->getShopIdByStoreId();
            if($shopId) {
                $collection = Mage::getModel("feedbackcompany/reviews")->getCollection()
                    ->setOrder('date_created', 'DESC')
                    ->addFieldToFilter('status', 1)
                    ->addFieldToFilter('sidebar', 1)
                    ->addFieldToFilter('shop_id', array('eq' => array($shopId)))
                    ->setPageSize($qty)
                    ->load();

                return $collection;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getLatestReview()
    {
        if (Mage::getStoreConfig('feedbackcompany/block/medium_review')) {
            $shopId = Mage::getModel("feedbackcompany/stats")->getShopIdByStoreId();
            if($shopId) {
                $review = Mage::getModel("feedbackcompany/reviews")->getCollection();
                $review->setOrder('date_created', 'DESC');
                $review->addFieldToFilter('status', 1);
                $review->addFieldToFilter('review_text', array('notnull' => true));
                $review->addFieldToFilter('shop_id', array('eq' => array($shopId)));
                $review->setPageSize(1);
                return $review->getFirstItem();
            }
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
            return Mage::getModel("feedbackcompany/stats")->getFeedbackUrl();
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
     * @param      $data
     * @param null $excludeReview
     * @param null $include
     *
     * @return array
     */
    public function getQuestions($data, $excludeReview = null, $include = null)
    {
        $data = json_decode($data, true);
        $questions = array();
        foreach ($data as $row) {
            if (!empty($include)) {
                if ($row['type'] != $include) {
                    continue;
                }
            }

            $row['value_html'] = $row['value'];
            if ($row['type'] == 'score' || $row['type'] == 'final_score') {
                if ($row['value'] < 1) {
                    continue;
                }

                $row['value_html'] = round($row['value']) . '/5';
            }

            if ($row['type'] == 'main_open' && $excludeReview) {
                continue;
            }

            $questions[$row['orderNr']] = $row;
        }

        return $questions;
    }

    /**
     * @param $data
     *
     * @return bool|string
     */
    public function getCustomerRecommend($data)
    {
        if ($data == 1) {
            return $this->__('Yes');
        }

        if ($data == 0) {
            return $this->__('No');
        }

        return false;
    }

}