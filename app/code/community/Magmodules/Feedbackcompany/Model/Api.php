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

    const XML_CLIENT_ID = 'feedbackcompany/general/client_id';
    const XML_CLIENT_SECRET = 'feedbackcompany/general/client_secret';
    const FBC_OAUTH2_TOKEN_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/oauth2/token';
    const FBC_FEEDBACK_URL = 'https://connect.feedbackcompany.nl/feedback/';

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getClientId($storeId)
    {
        return Mage::getStoreConfig(self::XML_CLIENT_ID, $storeId);
    }

    /**
     * @param $url
     * @param $storeId
     *
     * @return array|bool|mixed
     */
    public function makeRequest($url, $storeId)
    {
        $clientToken = $this->getOauthToken($storeId);
        if ($clientToken['status'] == 'ERROR') {
            return $clientToken;
        } else {
            $clientToken = $clientToken['client_token'];
        }

        $request = curl_init();
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $clientToken));
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        $apiResult = json_decode($content = curl_exec($request), true);

        return $apiResult;
    }

    /**
     * @param $storeId
     *
     * @return array|bool
     */
    public function getOauthToken($storeId)
    {
        $clientId = Mage::getStoreConfig(self::XML_CLIENT_ID, $storeId);
        $clientSecret = Mage::getStoreConfig(self::XML_CLIENT_SECRET, $storeId);

        if (!empty($clientId) && !empty($clientSecret)) {
            $getArray = array(
                "client_id"     => $clientId,
                "client_secret" => $clientSecret,
                "grant_type"    => "authorization_code"
            );

            $feedbackconnect = curl_init(self::FBC_OAUTH2_TOKEN_URL . '?' . http_build_query($getArray));
            curl_setopt($feedbackconnect, CURLOPT_VERBOSE, 1);
            curl_setopt($feedbackconnect, CURLOPT_FAILONERROR, false);
            curl_setopt($feedbackconnect, CURLOPT_HEADER, 0);
            curl_setopt($feedbackconnect, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($feedbackconnect, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($feedbackconnect, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($feedbackconnect, CURLOPT_TIMEOUT, 30);
            $response = json_decode(curl_exec($feedbackconnect));
            curl_close($feedbackconnect);

            if (isset($response->access_token)) {
                return array('status' => 'OK', 'client_token' => $response->access_token);
            } else {
                if (isset($response->description)) {
                    return array('status' => 'ERROR', 'error' => $response->description);
                } else {
                    return array('status' => 'ERROR', 'error' => 'No response from API');
                }
            }
        }

        return false;
    }

    /**
     * @param $order
     *
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
                    $log->addToLog('invitation', $storeId, '', $reason, $startTime, $crontype, '', $order->getId());
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
                $url = self::FBC_FEEDBACK_URL . '?' . $post;
                $feedbackconnect = curl_init($url);
                curl_setopt($feedbackconnect, CURLOPT_VERBOSE, 1);
                curl_setopt($feedbackconnect, CURLOPT_FAILONERROR, false);
                curl_setopt($feedbackconnect, CURLOPT_HEADER, 0);
                curl_setopt($feedbackconnect, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($feedbackconnect, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($feedbackconnect, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($feedbackconnect, CURLOPT_TIMEOUT, 60);
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
                $log->addToLog(
                    'invitation', $order->getStoreId(), '', $responseHtml, $startTime, $crontype,
                    $url, $order->getId()
                );

                return true;
            }
        }

        return false;
    }

    /**
     * @param null $type
     *
     * @return array
     */
    public function getStoreIds($type = null)
    {
        $storeIds = array();
        $stores = Mage::getModel('core/store')->getCollection();
        foreach ($stores as $store) {
            if ($store->getIsActive()) {
                if ($type == 'cron') {
                    $enabled = Mage::helper('feedbackcompany')->isCronEnabled($store->getId());
                } elseif ($type == 'prcron') {
                    $enabled = Mage::helper('feedbackcompany')->isPrCronEnabled($store->getId());
                } else {
                    $enabled = Mage::helper('feedbackcompany')->isEnabled($store->getId());
                }
                $clientId = Mage::getStoreConfig(self::XML_CLIENT_ID, $store->getId());
                if ($enabled && $clientId) {
                    $storeIds[$clientId] = $store->getId();
                }
            }
        }

        return $storeIds;
    }

}