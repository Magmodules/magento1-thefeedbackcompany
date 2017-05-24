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

class Magmodules_Feedbackcompany_Model_Log extends Mage_Core_Model_Abstract
{

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('feedbackcompany/log');
    }

    /**
     * @param        $type
     * @param        $sId
     * @param string $review
     * @param string $res
     * @param string $t
     * @param string $cron
     * @param string $aUrl
     * @param string $oId
     */
    public function addToLog($type, $sId, $review = '', $res = '', $t = '', $cron = '', $aUrl = '', $oId = '')
    {
        if (Mage::getStoreConfig('feedbackcompany/log/enabled')) {
            $company = isset($review['company']) ? $review['company'] : '';

            if (empty($company)) {
                $clientId = Mage::getStoreConfig('feedbackcompany/general/client_id', $sId);
                $stats = Mage::getModel('feedbackcompany/stats')->getCollection()
                    ->addFieldToFilter('client_id', $clientId)->getFirstItem();
                $company = $stats->getCompany();
            }

            $data = array(
                'type'           => $type,
                'shop_id'        => Mage::getModel('feedbackcompany/api')->getClientId($sId),
                'store_id'       => $sId,
                'company'        => $company,
                'review_updates' => isset($review['update']) ? $review['update'] : '',
                'review_new'     => isset($review['new']) ? $review['new'] : '',
                'response'       => $res,
                'order_id'       => $oId,
                'cron'           => $cron,
                'date'           => date('Y-m-d H:i:s'),
                'time'           => isset($t) ? (microtime(true) - $t) : '',
                'api_url'        => $aUrl
            );
            Mage::getModel('feedbackcompany/log')->setData($data)->save();
        }
    }

}