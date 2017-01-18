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
class Magmodules_Feedbackcompany_Model_Stats extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/stats');
    }

    public function processFeed($feed, $storeId = 0)
    {
        $result = array();
        $shopId = Mage::getStoreConfig('feedbackcompany/general/api_id', $storeId);
        $company = Mage::getStoreConfig('feedbackcompany/general/company', $storeId);

        if (!empty($feed->detailslink)) {
            if ($storeId == 0) {
                $config = Mage::getModel('core/config');
                $config->saveConfig('feedbackcompany/general/url', $feed->detailslink, 'default', $storeId);
            } else {
                $config = Mage::getModel('core/config');
                $config->saveConfig('feedbackcompany/general/url', $feed->detailslink, 'stores', $storeId);
                if (!Mage::getStoreConfig('feedbackcompany/general/url', 0)) {
                    $config->saveConfig('feedbackcompany/general/url', $feed->detailslink, 'default', 0);
                }
            }

            if ($feed->noReviews > 0) {
                $id = $this->loadbyShopId($shopId)->getId();
                $score = floatval($feed->score);
                $score = ($score * 10);
                $scoremax = ($feed->scoremax * 10);
                $votes = $feed->noReviews;

                $this->setId($id)
                    ->setShopId($shopId)
                    ->setCompany($company)
                    ->setScore($score)
                    ->setScoremax($scoremax)
                    ->setVotes($votes)
                    ->save();

                $result['status'] = 'success';
                $result['company'] = $company;
                $result['score'] = $score;
            }
        } else {
            $result['status'] = 'error';
            $result['msg'] = Mage::helper('core/string')->truncate(strip_tags($feed), '500');
        }

        return $result;
    }

    public function loadbyShopId($shopId)
    {
        $this->_getResource()->load($this, $shopId, 'shop_id');
        return $this;
    }

    public function processOverall()
    {
        $stats = Mage::getModel('feedbackcompany/stats')->getCollection();
        $stats->addFieldToFilter('shop_id', array('neq' => '0'));
        $id = $this->loadbyShopId(0)->getId();

        $score = '';
        $scoremax = '';
        $votes = '';
        $i = 0;

        foreach ($stats as $stat) {
            $score += $stat->getScore();
            $scoremax += $stat->getScoremax();
            $votes += $stat->getVotes();
            $i++;
        }

        if ($i > 0) {
            $score = ($score / $i);
            $scoremax = ($scoremax / $i);
            $company = 'Overall';
        }

        Mage::getModel('feedbackcompany/stats')
            ->setId($id)
            ->setShopId(0)
            ->setCompany($company)
            ->setScore($score)
            ->setScoremax($scoremax)
            ->setVotes($votes)
            ->save();
    }

}