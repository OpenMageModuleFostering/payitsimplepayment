<?php

class PayItSimple_Payment_Model_Source_Installments
{
    public function toOptionArray()
    {
        return array(
            2 => array('value' => '2', 'label' => Mage::helper('pis_payment')->__('2 Installments')),
            array('value' => '3', 'label' => Mage::helper('pis_payment')->__('3 Installments')),
            array('value' => '4', 'label' => Mage::helper('pis_payment')->__('4 Installments')),
            array('value' => '5', 'label' => Mage::helper('pis_payment')->__('5 Installments')),
            array('value' => '6', 'label' => Mage::helper('pis_payment')->__('6 Installments')),
            array('value' => '7', 'label' => Mage::helper('pis_payment')->__('7 Installments')),
            array('value' => '8', 'label' => Mage::helper('pis_payment')->__('8 Installments')),
            array('value' => '9', 'label' => Mage::helper('pis_payment')->__('9 Installments')),
            array('value' => '10', 'label' => Mage::helper('pis_payment')->__('10 Installments')),
            array('value' => '11', 'label' => Mage::helper('pis_payment')->__('11 Installments')),
            array('value' => '12', 'label' => Mage::helper('pis_payment')->__('12 Installments')),
        );
    }
}