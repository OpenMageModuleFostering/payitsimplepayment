<?php

class PayItSimple_Payment_Block_Info_Pis extends Mage_Payment_Block_Info_Cc
{
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(array(
            Mage::helper('payment')->__('No# of Installments')  => $info->getInstallmentsNo(),
        ));
        return $transport;
    }
}