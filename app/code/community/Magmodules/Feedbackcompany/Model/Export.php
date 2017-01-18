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
class Magmodules_Feedbackcompany_Model_Export extends Mage_Core_Model_Abstract
{

    /**
     * @param $reviews
     * @param $storeId
     *
     * @return array
     */
    public function getFeed($reviews, $storeId)
    {
        $csvData = array();
        $csvData[] = $this->getHeader();
        foreach ($reviews as $reviewId) {
            $review = Mage::getModel('review/review')->load($reviewId);
            $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($review->getEntityPkValue());
            if ($review && $product) {
                $text = trim($review->getTitle()) . ' ' . trim(preg_replace('/\s+/', ' ', $review->getDetail()));
                if (strlen($text) > 1) {
                    $date = date('Ymd', strtotime($review->getCreatedAt()));
                    $name = Mage::helper('core/string')->truncate($review->getNickname(), 250);
                    $email = '';
                    $gender = '';
                    $city = '';
                    $country = '';
                    $productName = trim($product->getName());
                    $productReview = str_replace(';', '', $text);
                    $productId = $review->getEntityPkValue();
                    $productSku = $product->getSku();
                    $productUrl = $product->getProductUrl();
                    if ($productUrl) {
                        $productUrl = preg_replace('/\?.*/', '', $productUrl);
                    }

                    $productOpinionId = $review->getFeedbackcompanyId();
                    $score = array();

                    $votes = Mage::getModel('rating/rating_option_vote')->getResourceCollection()
                        ->setReviewFilter($reviewId)
                        ->setStoreFilter($storeId)
                        ->load();

                    foreach ($votes as $vote) {
                        if ($vote->getPercent() > 0) {
                            $score[] = $vote->getPercent();
                        }
                    }

                    if (!empty($votes) > 0) {
                        $productScore = round(((array_sum($score) / count($votes)) / 20), 2);
                    } else {
                        $productScore = '';
                    }

                    if ($review->getCustomerId()) {
                        $customer = Mage::getModel('customer/customer')->load($review->getCustomerId());
                        $address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
                        $email = $customer->getEmail();
                        $city = $address->getCity();
                        $country = $address->getCountry();
                    }

                    $csvData[] = array(
                        $date,
                        $name,
                        $email,
                        $gender,
                        '',
                        $city,
                        $country,
                        '',
                        $productName,
                        $productScore,
                        $productReview,
                        $productUrl,
                        $productId,
                        $productSku,
                        $productOpinionId
                    );
                }
            }
        }

        return $csvData;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        $header = array(
            'date',
            'name',
            'email',
            'gender',
            'age',
            'city',
            'country',
            'vestiging',
            'product_name',
            'product_score',
            'product_review',
            'product_url',
            'product_id',
            'product_sku',
            'product_opinion_id',
        );

        return $header;
    }

}