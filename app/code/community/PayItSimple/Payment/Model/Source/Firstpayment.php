<?php 

class PayItSimple_Payment_Model_Source_Firstpayment
{
	public function toOptionArray()
	{
		return array(
            array('value' => 'equal', 'label' => Mage::helper('pis_payment')->__('Equal to Monthly Payment')),
            array('value' => 'shipping_taxes', 'label' => Mage::helper('pis_payment')->__('Add Shipping & Taxes')),
            array('value' => 'shipping', 'label' => Mage::helper('pis_payment')->__('Add Shipping')),
            array('value' => 'tax', 'label' => Mage::helper('pis_payment')->__('Add Taxes')),
            array('value' => 'percentage', 'label' => Mage::helper('pis_payment')->__('Equal to percentage of the order [X]')),
        );
	}
}