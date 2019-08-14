<?php

class PayItSimple_Payment_Model_Source_Selectinstallmentsetup
{
    public function toOptionArray()
    {
        return array(
            2 => array('value' => 'fixed', 'label' => Mage::helper('pis_payment')->__('Fixed')),
            array('value' => 'depending_on_cart_total', 'label' => Mage::helper('pis_payment')->__('Depending on cart total')),
        );
    }
}