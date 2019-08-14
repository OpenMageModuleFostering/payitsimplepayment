<?php
class PayItSimple_Payment_Model_PisMethod extends Mage_Payment_Model_Method_Cc
{
	protected $_code = 'pis_cc';
    protected $_canSaveCc   = true;
	protected $_formBlockType = 'pis_payment/form_pis';
	protected $_infoBlockType = 'pis_payment/info_pis';
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canCaptureOnce              = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setInstallmentsNo($data->getInstallmentsNo());
        $info->setAdditionalInformation('terms',$data->getTerms());
        return parent::assignData($data);
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        $no = $info->getInstallmentsNo();
        $terms= $info->getAdditionalInformation('terms');
        $errorMsg = '';
        if (empty($no)) {
            $errorMsg = $this->_getHelper()->__('Installments are required fields');
        }
        if (empty($terms)) {
            $errorMsg = $this->_getHelper()->__('You should accept terms and conditions');
        }
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return parent::validate();
    }

    /**
     * Authorize payment abstract method
     *
     * @param Varien_Object $payment fgfgf
     * @param float         $amount  fgfgfgfg
     *
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            Mage::throwException(
                Mage::helper('payment')->__('Authorize action is not available.')
            );
        }
        $api = $this->_initApi($this->getStore());
        $result = $this->createInstallmentPlan($api, $payment, $amount);

        $payment->setTransactionId($result['InstallmentPlanNumber']);
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionApproved(true);
        foreach (
            array(
                'ConsumerFullName',
                'Email',
                'Amount',
                'InstallmentNumber'
            ) as $param) {

            unset($result[$param]);

        }
        $st = $api->getInstallmentPlanStatusList();
        $result['InstallmentPlanStatus'] = $st[$result['InstallmentPlanStatus']];
        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $result
        );
        $order = $payment->getOrder();
        $order->addStatusToHistory(
            $order->getStatus(),
            'Payment InstallmentPlan was created with number ID: '
            . $result['InstallmentPlanNumber'],
            false
        );
        //$order->save();

        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(
                Mage::helper('payment')->__('Capture action is not available.')
            );
        }
        if (!$payment->getAuthorizationTransaction()) {
            $this->authorize($payment, $amount);
            $authNumber = $payment->getTransactionId();
        } else {
            $authNumber = $payment->getAuthorizationTransaction()->getTxnId();
        }
        $api = $this->_initApi($this->getStore());
        $result = $api->notifyOrderShipped(
            array('InstallmentPlanNumber' => $authNumber)
        );
        $this->debugData('REQUEST: ' . $api->getRequest());
        $this->debugData('RESPONSE: ' . $api->getResponse());
        if (!$result) {
            $e = $api->getError();
            Mage::throwException($e['code'].' '.$e['message']);
        }

        $payment->setIsTransactionClosed(1);
        $order = $payment->getOrder();

        $order->addStatusToHistory(
            false,
            'Payment NotifyOrderShipped was sent with number ID: '.$authNumber, false
        );
        $order->save();

        return $this;
    }

    /**
     * @param $api     PayItSimple_Payment_Model_Api
     * @param $payment Mage_Sales_Model_Order_Payment
     *
     * @return array|bool
     * @throws Mage_Payment_Exception
     */
    protected function createInstallmentPlan($api, $payment, $amount)
    {
        $order = $payment->getOrder();
        $billingaddress = $order->getBillingAddress();
        $address = $billingaddress->getData('street') . ' '
            . $billingaddress->getData('city') . ' '
            . $billingaddress->getData('region');
        $ccTypes = $api->getCcTypesAvailable();
        $params = array(
            'ConsumerFullName' => $order->getCustomerName(),
            'Email' => $order->getCustomerEmail(),
            'AvsAddress' => $address,
            'AvsZip' => $billingaddress->getData('postcode'),
            'CountryId' => '840', // USA only
            'AmountBeforeFees' => $amount,
            'CardHolder' => $billingaddress->getData('firstname')
                . ' ' .  $billingaddress->getData('lastname'),
            'CardTypeId' => $ccTypes[$payment->getCcType()],
            'CardNumber' => $payment->getCcNumber(),
            'CardExpMonth' => $payment->getCcExpMonth(),
            'CardExpYear' => $payment->getCcExpYear(),
            'CardCvv' => $payment->getCcCid(),
            'InstallmentNumber' => $payment->getInstallmentsNo(),
            'ParamX' => $order->getIncrementId()
        );
        $result = $api->createInstallmentPlan($params);
        $this->debugData('REQUEST: ' . $api->getRequest());
        $this->debugData('RESPONSE: ' . $api->getResponse());
        if (!$result){
            $e = $api->getError();
            Mage::throwException($e['code'].' '.$e['message']);
        }
        return $result;
    }

    /**
     * @param $storeId int
     *
     * @return PayItSimple_Payment_Model_Api
     * @throws Mage_Payment_Exception
     */
    protected function _initApi($storeId = null){
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $api = $this->getApi();
        if ($api->isLogin()) {
            return $api;
        }
        $result = $api->login(
            $this->getApiUrl(),
            array(
                'ApiKey' => $this->getConfigData('api_terminal_key', $storeId),
                'UserName' => $this->getConfigData('api_username'),
                'Password' => $this->getConfigData('api_password')
            )
        );
        $this->debugData('REQUEST: ' . $api->getRequest());
        $this->debugData('RESPONSE: ' . $api->getResponse());
        if (!$result || !$api->isLogin()){
            $e = $api->getError();
            Mage::throwException($e['code'].' '.$e['message']);
        }
        return $api;
    }

    public function getApi(){
        return Mage::getSingleton('pis_payment/api');
    }

    public function getApiUrl() {
        if ($this->getConfigData('sandbox_flag')) {
            return $this->getConfigData('api_url_sandbox');
        }
        return $this->getConfigData('api_url');
    }


}