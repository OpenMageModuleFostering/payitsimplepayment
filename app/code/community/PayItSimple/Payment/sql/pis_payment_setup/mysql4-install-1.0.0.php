<?php
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer->startSetup();
$installer->run("
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `installments_no` VARCHAR( 10 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `installments_no` VARCHAR( 10 ) NOT NULL ;
");
$installer->endSetup();