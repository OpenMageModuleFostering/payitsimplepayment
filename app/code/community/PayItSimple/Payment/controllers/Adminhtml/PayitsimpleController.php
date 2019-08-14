<?php

class PayItSimple_Payment_Adminhtml_PayitsimpleController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction()
    {
        $storeId = $this->getRequest()->getParam('store_id', 0);
        $paymentMethod = Mage::getModel('pis_payment/pisMethod');
        $api = $paymentMethod->getApi();
        $params = array(
            'ApiKey' => $paymentMethod->getConfigData('api_terminal_key', $storeId),
            'UserName' => $paymentMethod->getConfigData('api_username'),
            'Password' => $paymentMethod->getConfigData('api_password')
        );
        $result = $api->login($paymentMethod->getApiUrl(), $params);
        $paymentMethod->debugData('REQUEST: ' . $api->getRequest());
        $paymentMethod->debugData('RESPONSE: ' . $api->getResponse());
        $message = ($paymentMethod->getConfigData('sandbox_flag'))?'[Sandbox Mode] ':'[Production mode] ';
        if ($result) {
            $message .= 'Successfully login! API available!';
        } else {
            $error = $api->getError();
            $message .= $error['code'] . ' - ERROR: '. $error['message'];
        }
        Mage::app()->getResponse()->setBody($message);
    }
}