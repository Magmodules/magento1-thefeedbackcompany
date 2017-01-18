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
$installer->run(
    "
	DROP TABLE IF EXISTS {$this->getTable('feedbackcompany_reviews')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('feedbackcompany_reviews')} (
		`review_id` int(10) NOT NULL AUTO_INCREMENT,
		`shop_id` int(5) NOT NULL,
		`company` varchar(255) DEFAULT NULL,
		`feedback_id` int(5) NOT NULL,
		`review_text` text NOT NULL,
		`score` smallint(6) DEFAULT '0',
		`score_max` smallint(6) DEFAULT '0',
		`score_aftersales` smallint(6) DEFAULT '0',
		`score_checkout` smallint(6) DEFAULT '0',
		`score_information` smallint(6) DEFAULT '0',
		`score_friendly` smallint(6) DEFAULT '0',
		`score_leadtime` smallint(6) DEFAULT '0',
		`score_responsetime` smallint(6) DEFAULT '0',
		`score_order` smallint(6) DEFAULT '0',
		`customer_name` varchar(255) DEFAULT NULL,
		`customer_recommend` varchar(255) DEFAULT NULL,
		`customer_active` varchar(255) DEFAULT NULL,
		`customer_sex` varchar(255) DEFAULT NULL,
		`customer_age` smallint(6) DEFAULT '0',
		`purchased_products` varchar(255) DEFAULT NULL,
		`text_positive` varchar(255) DEFAULT NULL,
		`text_improvements` varchar(255) DEFAULT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`sidebar` tinyint(1) NOT NULL DEFAULT '1',
		`status` tinyint(5) NOT NULL DEFAULT '1',
		PRIMARY KEY (`review_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('feedbackcompany_log')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('feedbackcompany_log')} (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`type` varchar(255) NOT NULL,
		`shop_id` varchar(255) NOT NULL,
		`company` varchar(255) DEFAULT NULL,
		`review_update` int(5) DEFAULT '0',
		`review_new` int(5) DEFAULT '0',
		`response` text,
		`order_id` int(10) DEFAULT NULL,
		`cron` varchar(255) DEFAULT NULL,
		`date` datetime NOT NULL,
		`time` varchar(255) NOT NULL,
		`api_url` text,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('feedbackcompany_stats')};
	CREATE TABLE IF NOT EXISTS {$this->getTable('feedbackcompany_stats')} (
		`id` int(5) NOT NULL AUTO_INCREMENT,
		`company` varchar(255) DEFAULT NULL,
		`shop_id` int(5) NOT NULL,
		`score` smallint(6) DEFAULT '0',
		`scoremax` smallint(6) DEFAULT '0',
		`votes` int(5) DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;	
"
);
$installer->endSetup(); 