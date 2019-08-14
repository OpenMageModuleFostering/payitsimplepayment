<?php

class PayItSimple_Payment_Model_Source_Cctype
{
    public function toOptionArray()
    {
        $options =  array();
        $apiCcTypes = Mage::getSingleton('pis_payment/api')->getCcTypesAvailable();
        foreach (Mage::getSingleton('payment/config')->getCcTypes() as $code => $name) {
            if (!isset($apiCcTypes[$code])) continue;
            $options[] = array(
               'value' => $code,
               'label' => $name
            );
        }

        return $options;
    }
}