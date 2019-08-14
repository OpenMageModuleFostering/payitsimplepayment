<?php

class PayItSimple_Payment_Model_Source_Action
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('pis_payment')->__('Charge my consumer at the time of the purchase')
            ),
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'label' => Mage::helper('pis_payment')->__('Charge my consumer when the shipment is ready')
            ),

        );
    }
}
