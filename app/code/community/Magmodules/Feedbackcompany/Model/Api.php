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
class Magmodules_Feedbackcompany_Model_Api extends Mage_Core_Model_Abstract
{

    /**
     * @param int $storeId
     * @param $type
     * @return bool
     */
    public function processFeed($storeId = 0, $type)
    {
        if ($feed = $this->getFeed($storeId, $type)) {
            $results = Mage::getModel('feedbackcompany/reviews')->processFeed($feed, $type, $storeId);
            $results['stats'] = Mage::getModel('feedbackcompany/stats')->processFeed($feed, $storeId);

            return $results;
        }

        return false;
    }


    /**
     * @param $storeId
     * @param string $type
     * @param string $interval
     * @return bool|SimpleXMLElement|array
     */
    public function getFeed($storeId, $type = '', $interval = '')
    {
        if ($type == 'productreviews') {
            $result = array();
            $clientToken = Mage::helper('feedbackcompany')->getUncachedConfigValue(
                'feedbackcompany/productreviews/client_token',
                $storeId
            );
            if (!$clientToken) {
                $clientToken = $this->getOauthToken($storeId);
                if ($clientToken['status'] == 'ERROR') {
                    return $clientToken;
                } else {
                    $clientToken = $clientToken['client_token'];
                }
            }

            $apiUrl = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/getrecent/';

            $request = curl_init();
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($request, CURLOPT_URL, $apiUrl . '?interval=' . $interval . '&type=product&unixts=1');
            curl_setopt($request, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $clientToken));
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $apiResult = json_decode($content = curl_exec($request));

            if ($apiResult) {
                if (isset($apiResult->message)) {
                    if ($apiResult->message == 'OK') {
                        $result['status'] = 'OK';
                        $result['feed'] = $apiResult->data[0];

                        return $result;
                    }
                }

                $config = Mage::getModel('core/config');
                $config->saveConfig('feedbackcompany/productreviews/client_token', '', 'stores', $storeId);
                $result['status'] = 'ERROR';
                $result['error'] = $apiResult->error;

                return $result;
            } else {
                $result['status'] = 'ERROR';
                $result['error'] = Mage::helper('feedbackcompany')->__('Error connect to the API.');

                return $result;
            }
        } else {
            $apiId = trim(Mage::getStoreConfig('feedbackcompany/general/api_id', $storeId));
            $apiUrl = 'https://beoordelingen.feedbackcompany.nl/samenvoordeel/scripts/flexreview/getreviewxml.cfm';

            if ($type == 'stats') {
                $apiUrl = $apiUrl . '?ws=' . $apiId . '&publishDetails=0&nor=0&Basescore=10';
            }

            if (($type == 'reviews') || ($type == 'history')) {
                $apiUrl = $apiUrl . '?ws=' . $apiId;
                $apiUrl .= '&publishIDs=1&nor=100&publishDetails=1&publishOnHold=0&sort=desc&emlpass=test';
                $apiUrl .= '&publishCompResponse=1&Basescore=10';
            }

            if ($type == 'all') {
                $apiUrl = $apiUrl . '?ws=' . $apiId;
                $apiUrl .= '&publishIDs=1&nor=10000&publishDetails=1&publishOnHold=0&sort=desc&emlpass=test';
                $apiUrl .= '&publishCompResponse=1&Basescore=10';
            }

            if ($apiId) {
                $xml = @simplexml_load_file($apiUrl);
                if ($xml) {
                    return $xml;
                }
            }
        }

        return false;
    }

    public function getOauthToken($storeId)
    {
        $clientId = Mage::getStoreConfig('feedbackcompany/productreviews/client_id', $storeId);
        $clientSecret = Mage::getStoreConfig('feedbackcompany/productreviews/client_secret', $storeId);

        if (!empty($clientId) && !empty($clientSecret)) {
            $url = "https://beoordelingen.feedbackcompany.nl/api/v1/oauth2/token";

            $getArray = array(
                "client_id" => $clientId,
                "client_secret" => $clientSecret,
                "grant_type" => "authorization_code"
            );

            $feedbackconnect = curl_init($url . '?' . http_build_query($getArray));

            curl_setopt($feedbackconnect, CURLOPT_VERBOSE, 1);
            curl_setopt($feedbackconnect, CURLOPT_FAILONERROR, false);
            curl_setopt($feedbackconnect, CURLOPT_HEADER, 0);
            curl_setopt($feedbackconnect, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($feedbackconnect, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($feedbackconnect, CURLOPT_SSL_VERIFYPEER, false);
            $response = json_decode(curl_exec($feedbackconnect));
            curl_close($feedbackconnect);


            if (isset($response->access_token)) {
                $storeIds = Mage::getModel('feedbackcompany/productreviews')->getAllStoreViews($storeId);
                $config = Mage::getModel('core/config');
                foreach ($storeIds as $storeId) {
                    $config->saveConfig(
                        'feedbackcompany/productreviews/client_token', $response->access_token,
                        'stores', $storeId
                    );
                }

                $result = array();
                $result['status'] = 'OK';
                $result['client_token'] = $response->access_token;

                return $result;
            } else {
                if ($response->description) {
                    $result = array();
                    $result['status'] = 'ERROR';
                    $result['error'] = $response->description;

                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * @param $order
     * @return bool
     */
    public function sendInvitation($order)
    {
        $storeId = $order->getStoreId();
        $invStatus = Mage::getStoreConfig('feedbackcompany/invitation/status', $storeId);
        $dateNow = Mage::getModel('core/date')->timestamp(time());
        $dateOrder = Mage::getModel('core/date')->timestamp($order->getCreatedAt());
        $dateDiff = (($dateOrder - $dateNow) / 86400);
        $backlog = Mage::getStoreConfig('feedbackcompany/invitation/backlog', $storeId);
        $sent = $order->getFeedbackSent();
        $log = Mage::getModel('feedbackcompany/log');

        if ($backlog < 1) {
            $backlog = 30;
        }

        if (($order->getStatus() == $invStatus) && ($dateDiff < $backlog) && (!$sent)) {
            $startTime = microtime(true);
            $crontype = 'orderupdate';
            $apiKey = Mage::getStoreConfig('feedbackcompany/invitation/connector', $storeId);
            $delay = Mage::getStoreConfig('feedbackcompany/invitation/delay', $storeId);
            $resend = Mage::getStoreConfig('feedbackcompany/invitation/resend', $storeId);
            $remindDelay = Mage::getStoreConfig('feedbackcompany/invitation/remind_delay', $storeId);
            $minOrder = Mage::getStoreConfig('feedbackcompany/invitation/min_order_total', $storeId);
            $excludeCat = Mage::getStoreConfig('feedbackcompany/invitation/exclude_category', $storeId);
            $productreviews = Mage::getStoreConfig('feedbackcompany/productreviews/enabled', $storeId);
            $email = $order->getCustomerEmail();
            $orderNumber = $order->getIncrementID();
            $orderTotal = $order->getGrandTotal();
            $aanhef = $order->getCustomerName();
            $checkSum = 0;
            $categories = array();
            $excludeReason = array();

            $request = array();
            $request['action'] = 'sendInvitation';

            // Exclude by Category
            $exclCategories = '';
            if ($excludeCat) {
                if ($ids = Mage::getStoreConfig('feedbackcompany/invitation/exclude_categories', $storeId)) {
                    $exclCategories = explode(',', $ids);
                }
            }

            // Get all Products
            $i = 1;
            $filtercode = array();
            $websiteUrl = Mage::app()->getStore($storeId)
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $mediaUrl = Mage::app()->getStore($storeId)
                    ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog' . DS . 'product';

            foreach ($order->getAllVisibleItems() as $item) {
                $filtercode[] = urlencode(trim($item->getSku()));
                $filtercode[] = urlencode(trim($item->getName()));
                if ($productreviews) {
                    $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getProductId());
                    if (($product->getStatus() == '1') && ($product->getVisibility() != '1')) {
                        $varUrl = urlencode('product_url[' . $i . ']');
                        $varText = urlencode('product_text[' . $i . ']');
                        $varId = urlencode('product_ids[' . $i . ']');
                        $varPhoto = urlencode('product_photo[' . $i . ']');
                        if ($product->getUrlPath()) {
                            $deeplink = $websiteUrl . $product->getUrlPath();
                            $imageUrl = '';
                            if ($product->getImage() && ($product->getImage() != 'no_selection')) {
                                $imageUrl = $mediaUrl . $product->getImage();
                            }

                            $request[$varUrl] = urlencode($deeplink);
                            $request[$varText] = urlencode(trim($product->getName()));
                            $request[$varId] = urlencode('SKU=' . trim($product->getSku()));
                            $request[$varPhoto] = urlencode($imageUrl);
                            $i++;
                        }
                    }
                }

                if ($excludeCat) {
                    if (!$product) {
                        $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getProductId());
                    }

                    $categories = array_merge($categories, $product->getCategoryIds());
                }
            }

            $filtercode = implode(',', $filtercode);

            // Get Checksum
            for ($i = 0; $i < strlen($email); $i++) {
                $checkSum += ord($email[$i]);
            }

            $exclude = 0;
            if (!empty($minOrder)) {
                if ($minOrder >= $orderTotal) {
                    $exclude = 1;
                    $excludeReason[] = Mage::helper('feedbackcompany')->__('Below minimum order value');
                }
            }

            if ($order->getStatus() != $invStatus) {
                $exclude = 1;
            }

            if ($exclCategories) {
                foreach ($categories as $cat) {
                    if (in_array($cat, $exclCategories)) {
                        $exclude = 1;
                        $excludeReason[] = Mage::helper('feedbackcompany')->__('Category is excluded');
                    }
                }
            }

            if ($exclude == 1) {
                if ($excludeReason) {
                    $reason = implode(',', array_unique($excludeReason));
                    $reason = 'Not invited: ' . $reason;
                    $time = (microtime(true) - $startTime);
                    $log->addToLog('invitation', $storeId, '', $reason, $time, $crontype, '', $order->getId());
                } else {
                    return false;
                }
            } else {
                $request['filtercode'] = $filtercode;
                $request['Chksum'] = $checkSum;
                $request['orderNumber'] = $orderNumber;
                $request['resendIfDouble'] = $resend;
                $request['remindDelay'] = $remindDelay;
                $request['delay'] = $delay;
                $request['aanhef'] = urlencode($aanhef);
                $request['user'] = urlencode($email);
                $request['connector'] = $apiKey;

                $post = '';
                foreach (array_reverse($request) as $key => $value) {
                    $post .= '&' . $key . '=' . trim($value);
                }

                $post = trim($post, '&');

                // Connect to API
                $url = 'https://connect.feedbackcompany.nl/feedback/';
                $feedbackconnect = curl_init($url . '?' . $post);
                curl_setopt($feedbackconnect, CURLOPT_VERBOSE, 1);
                curl_setopt($feedbackconnect, CURLOPT_FAILONERROR, false);
                curl_setopt($feedbackconnect, CURLOPT_HEADER, 0);
                curl_setopt($feedbackconnect, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($feedbackconnect, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($feedbackconnect, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($feedbackconnect);
                curl_close($feedbackconnect);

                if ($response) {
                    if ($response == 'Request OK.') {
                        $order->setFeedbackSent(1)->save();
                        $responseHtml = $response;
                    } else {
                        $responseHtml = 'Error sending review request!';
                    }
                } else {
                    $responseHtml = 'No response from https://connect.feedbackcompany.nl';
                }

                // Write to log
                $time = (microtime(true) - $startTime);
                $log->addToLog('invitation', $order->getStoreId(), '', $responseHtml, $time, $crontype, $url . '?' . $post, $order->getId());

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getStoreIds($type = '')
    {
        $storeIds = array();
        $stores = Mage::getModel('core/store')->getCollection();
        if ($type == 'oauth') {
            foreach ($stores as $store) {
                if ($store->getIsActive()) {
                    $enabled = Mage::getStoreConfig('feedbackcompany/productreviews/enabled', $store->getId());
                    $clientId = Mage::getStoreConfig('feedbackcompany/productreviews/client_id', $store->getId());
                    if ($enabled && $clientId) {
                        $storeIds[] = $store->getId();
                    }
                }
            }

            return $storeIds;
        } else {
            $apiIds = array();
            foreach ($stores as $store) {
                if ($store->getIsActive()) {
                    $apiId = Mage::getStoreConfig('feedbackcompany/general/api_id', $store->getId());
                    if (!in_array($apiId, $apiIds)) {
                        $apiIds[] = $apiId;
                        $storeIds[] = $store->getId();
                    }
                }
            }

            return $storeIds;
        }
    }

}