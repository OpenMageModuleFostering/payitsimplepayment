<?php

class PayItSimple_Payment_Block_Adminhtml_System_Config_Notification extends Mage_Adminhtml_Block_Template
{
    public function isConfigured(){
        return Mage::getStoreConfig('payment/pis_cc/api_username');
    }
}
