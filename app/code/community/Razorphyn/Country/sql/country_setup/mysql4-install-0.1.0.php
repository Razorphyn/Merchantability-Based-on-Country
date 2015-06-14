<?php
	$installer=$this;
	$installer->startSetup();
	
	$installer->run("
			DROP TABLE IF EXISTS `{$installer->getTable('country/product')}`;
			CREATE TABLE `{$installer->getTable('country/product')}` (
			  `product_id` INT(11) unsigned NOT NULL,
			  `active` ENUM('0','1') NOT NULL DEFAULT '0',
			  `allowed` ENUM('0','1') NOT NULL 	DEFAULT '0',
			  `country` VARCHAR(255) DEFAULT NULL,
			  PRIMARY KEY (`product_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	
	$installer->run("
			DROP TABLE IF EXISTS `{$installer->getTable('country/category')}`;
			CREATE TABLE `{$installer->getTable('country/category')}` (
			  `category_id` INT(11) unsigned NOT NULL,
			  `active` ENUM('0','1') NOT NULL DEFAULT '0',
			  `allowed` ENUM('0','1') NOT NULL DEFAULT '0',
			  `country` VARCHAR(255) DEFAULT NULL,
			  PRIMARY KEY (`category_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	
	$setup = new Mage_Core_Model_Config();
	$setup->saveConfig('razorphyn/country/buttons', json_encode(array()), 'default', 0); 

	$installer->endSetup();
?>