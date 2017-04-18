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

$installer = $this;
$installer->startSetup();

// 1) UPDATE client_id path
$collection = Mage::getModel('core/config_data')->getCollection()
    ->addFieldToFilter('path', 'feedbackcompany/productreviews/client_id');

foreach ($collection as $row) {
    $row->setPath('feedbackcompany/general/client_id')->save();
}

// 2) UPDATE client_secret path
$collection = Mage::getModel('core/config_data')->getCollection()
    ->addFieldToFilter('path', 'feedbackcompany/productreviews/client_secret');

foreach ($collection as $row) {
    $row->setPath('feedbackcompany/general/client_secret')->save();
}

// 3) UNSET OLD VALUES
$unset = array(
    'feedbackcompany/productreviews/client_secret',
    'feedbackcompany/productreviews/client_id',
    'feedbackcompany/productreviews/client_token',
    'feedbackcompany/general/api_id',
    'feedbackcompany/general/url',
    'feedbackcompany/general/company'
);

$collection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', array('in' => $unset));
foreach ($collection as $row) {
    $row->delete();
}

// 4) ALTER STATS & REVIEW TABLE
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_stats')} ADD `review_url` VARCHAR(255) NULL AFTER `votes`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_stats')} ADD `recommends` INT(11) NULL AFTER `votes`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_stats')} ADD `client_id` VARCHAR(255) NULL AFTER `shop_id`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_reviews')} ADD `customer_city` VARCHAR(255) NULL AFTER `customer_age`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_reviews')} ADD `customer_email` VARCHAR(255) NULL AFTER `customer_city`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_reviews')} ADD `customer_country` VARCHAR(255) NULL AFTER `customer_email`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_reviews')} ADD `buy_online` VARCHAR(255) NULL AFTER `customer_country`");
$installer->run("ALTER TABLE {$this->getTable('feedbackcompany_reviews')} ADD `questions` TEXT NULL AFTER `status`");

$this->endSetup();