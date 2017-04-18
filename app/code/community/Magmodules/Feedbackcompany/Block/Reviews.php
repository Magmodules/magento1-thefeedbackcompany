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

class Magmodules_Feedbackcompany_Block_Reviews extends Mage_Core_Block_Template
{

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $pager = $this->getLayout()->createBlock('page/html_pager', 'feedbackcompany.pager');

        if (Mage::getStoreConfig('feedbackcompany/overview/enable_paging')) {
            $fieldPerPage = Mage::getStoreConfig('feedbackcompany/overview/paging_settings');
            $fieldPerPage = explode(',', $fieldPerPage);
            $fieldPerPage = array_combine($fieldPerPage, $fieldPerPage);
            $pager->setAvailableLimit($fieldPerPage);
        } else {
            $pager->setAvailableLimit(array('all' => 'all'));
        }

        $pager->setCollection($this->getReviews());
        $this->setChild('pager', $pager);
        $this->getReviews()->load();

        return $this;
    }

    /**
     * Magmodules_Feedbackcompany_Block_Reviews constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $shopId = Mage::getModel("feedbackcompany/stats")->getShopIdByStoreId();
        $collection = Mage::getModel('feedbackcompany/reviews')->getCollection();
        $collection->setOrder('date_created', 'DESC');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('shop_id', $shopId);
        $this->setReviews($collection);

        $stats = Mage::getModel('feedbackcompany/stats')->load($shopId, 'shop_id');
        $this->setStats($stats);
    }

    /**
     * @return mixed
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return mixed
     */
    public function getFormUrl()
    {
        return $this->helper('feedbackcompany')->getFormUrl();
    }

    /**
     * @return mixed
     */
    public function getReviewUrl()
    {
        return Mage::getStoreConfig('feedbackcompany/general/url');
    }

    /**
     * @return string
     */
    public function getPageIntro()
    {
        return nl2br(Mage::getStoreConfig('feedbackcompany/overview/intro'));
    }

    /**
     * @param $review
     * @return mixed
     */
    public function formatScoresReview($review)
    {
        return $this->helper('feedbackcompany')->formatScoresReview($review);
    }

}