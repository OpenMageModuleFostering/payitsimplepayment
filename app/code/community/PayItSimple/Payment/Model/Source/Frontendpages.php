<?php

class PayItSimple_Payment_Model_Source_Frontendpages
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'category', 'label' => Mage::helper('pis_payment')->__('Category pages')),
            array('value' => 'product', 'label' => Mage::helper('pis_payment')->__('Product pages')),
            array('value' => 'cart', 'label' => Mage::helper('pis_payment')->__('Shopping cart page')),
            array('value' => 'checkout', 'label' => Mage::helper('pis_payment')->__('Checkout page')),
            //array('value' => 'index', 'label' => Mage::helper('sip_payment')->__('Home page')),
        );
    }
}