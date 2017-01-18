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
class Magmodules_Feedbackcompany_Model_Reviews extends Mage_Core_Model_Abstract
{

    const CACHE_TAG = 'feedback_block';

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/reviews');
    }

    /**
     * @param     $feed
     * @param     $type
     * @param int $storeId
     *
     * @return array
     */
    public function processFeed($feed, $type, $storeId = 0)
    {
        $updates = 0;
        $new = 0;
        $apiId = Mage::getStoreConfig('feedbackcompany/general/api_id', $storeId);
        $company = Mage::getStoreConfig('feedbackcompany/general/company', $storeId);

        if (!empty($feed->detailslink)) {
            foreach ($feed->reviewDetails->reviewDetail as $review) {
                $feedbackId = $review->id;
                $score = ($review->score / 2);
                $scoreMax = ($review->scoremax / 2);
                $reviewText = $review->text;
                $scoreAftersales = ($review->score_aftersales / 2);
                $scoreCheckout = ($review->score_bestelgemak / 2);
                $scoreInformation = ($review->score_informatievoorziening / 2);
                $scoreFriendly = ($review->score_klantvriendelijk / 2);
                $scoreLeadtime = ($review->score_levertijd / 2);
                $scoreResponsetime = ($review->score_reactiesnelheid / 2);
                $scoreOrder = ($review->score_orderverloop / 2);
                $customerName = $review->user;
                $customerRecommend = $review->beveeltAan;
                $customerActive = $review->kooptvakeronline;
                $customerSex = $review->geslacht;
                $customerAge = $review->leeftijd;
                $purchasedProducts = $review->gekochtproduct;
                $textPositive = $review->sterkepunten;
                $textImprovements = $review->verbeterpunten;
                $companyResponse = $review->companyResponse;
                $date = $review->createdate;
                $dateCreated = substr($date, 0, 4) . '/' . substr($date, 4, 2) . '/' . substr($date, 6, 2);
                $inDatabase = $this->getCollection()->addFieldToFilter('feedback_id', $feedbackId)->getFirstItem();

                if ($inDatabase->getReviewId()) {
                    if (($type == 'history') || ($type == 'all')) {
                        $reviews = Mage::getModel('feedbackcompany/reviews');
                        $reviews->setReviewId($inDatabase->getReviewId())
                            ->setShopId($apiId)
                            ->setCompany($company)
                            ->setFeedbackId($feedbackId)
                            ->setReviewText($reviewText)
                            ->setScore($score)
                            ->setScoreMax($scoreMax)
                            ->setScoreAftersales($scoreAftersales)
                            ->setScoreCheckout($scoreCheckout)
                            ->setScoreInformation($scoreInformation)
                            ->setScoreFriendly($scoreFriendly)
                            ->setScoreLeadtime($scoreLeadtime)
                            ->setScoreResponsetime($scoreResponsetime)
                            ->setScoreOrder($scoreOrder)
                            ->setCustomerName($customerName)
                            ->setCustomerRecommend($customerRecommend)
                            ->setCustomerActive($customerActive)
                            ->setCustomerSex($customerSex)
                            ->setCustomerAge($customerAge)
                            ->setPurchasedProducts($purchasedProducts)
                            ->setTextPositive($textPositive)
                            ->setTextImprovements($textImprovements)
                            ->setCompanyResponse($companyResponse)
                            ->setDateCreated($dateCreated)
                            ->save();
                        $updates++;
                    }
                } else {
                    $reviews = Mage::getModel('feedbackcompany/reviews');
                    $reviews->setShopId($apiId)
                        ->setCompany($company)
                        ->setFeedbackId($feedbackId)
                        ->setReviewText($reviewText)
                        ->setScore($score)
                        ->setScoreMax($scoreMax)
                        ->setScoreAftersales($scoreAftersales)
                        ->setScoreCheckout($scoreCheckout)
                        ->setScoreInformation($scoreInformation)
                        ->setScoreFriendly($scoreFriendly)
                        ->setScoreLeadtime($scoreLeadtime)
                        ->setScoreResponsetime($scoreResponsetime)
                        ->setScoreOrder($scoreOrder)
                        ->setCustomerName($customerName)
                        ->setCustomerRecommend($customerRecommend)
                        ->setCustomerActive($customerActive)
                        ->setCustomerSex($customerSex)
                        ->setCustomerAge($customerAge)
                        ->setPurchasedProducts($purchasedProducts)
                        ->setTextPositive($textPositive)
                        ->setTextImprovements($textImprovements)
                        ->setCompanyResponse($companyResponse)
                        ->setDateCreated($dateCreated)
                        ->save();
                    $new++;
                }
            }

            $config = Mage::getModel('core/config');
            $config->saveConfig('feedbackcompany/reviews/lastrun', now(), 'default', 0);

            $result = array();
            $result['review_updates'] = $updates;
            $result['review_new'] = $new;
            $result['company'] = $company;
            return $result;
        } else {
            $result = array();
            $result['review_updates'] = 0;
            $result['review_new'] = 0;
            $result['company'] = '';
            return $result;
        }
    }

    /**
     * Flush Cache function
     */
    public function flushCache()
    {
        if (Mage::getStoreConfig('feedbackcompany/reviews/flushcache')) {
            Mage::app()->cleanCache(
                array(
                    Mage_Cms_Model_Block::CACHE_TAG,
                    Magmodules_Feedbackcompany_Model_Reviews::CACHE_TAG
                )
            );
        }
    }

}