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
            'CountryId' => $this->getCountryCodePIS($billingaddress->getCountryId()),
            'AmountBeforeFees' => $amount,
            'CardHolder' => $billingaddress->getData('firstname')
                . ' ' .  $billingaddress->getData('lastname'),
            'CardTypeId' => $ccTypes[$payment->getCcType()],
            'CardNumber' => $payment->getCcNumber(),
            'CardExpMonth' => $payment->getCcExpMonth(),
            'CardExpYear' => $payment->getCcExpYear(),
            'CardCvv' => $payment->getCcCid(),
            'InstallmentNumber' => $payment->getInstallmentsNo(),
            'ParamX' => $order->getIncrementId(),
			'CurrencyName' => Mage::app()->getStore()->getCurrentCurrencyCode()
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

    public function getCountryCodePIS($countryCode)
    {
        $countryIds = array(
            'AF' => 4,'AX' => 248,'AL' => 8,'DZ' => 12,'AS' => 16,'AD' => 20,'AO' => 24,'AI' => 660,'AQ' => 10,'AG' => 28,'AR' => 32,
            'AM' => 51,'AW' => 533,'AU' => 36,'AT' => 40,'AZ' => 31,'BS' => 44,'BH' => 48,'BD' => 50,'BB' => 52,'BY' => 112,'BE' => 56,
            'BZ' => 84,'BJ' => 204,'BM' => 60,'BT' => 64,'BO' => 68,'BA' => 70,'BW' => 72,'BV' => 74,'BR' => 76,'IO' => 86,'VG' => 92,
            'BN' => 96,'BG' => 100,'BF' => 854,'BI' => 108,'KH' => 116,'CM' => 120,'CA' => 124,'CV' => 132,'KY' => 136,'CF' => 140,
            'TD' => 148,'CL' => 152,'CN' => 156,'CX' => 162,'CC' => 166,'CO' => 170,'KM' => 174,'CG' => 180,'CD' => 178,'CK' => 184,
            'CR' => 188,'CI' => 384,'HR' => 191,'CU' => 192,'CY' => 196,'CZ' => 203,'DK' => 208,'DJ' => 262,'DM' => 212,'DO' => 214,
            'EC' => 218,'EG' => 818,'SV' => 222,'GQ' => 226,'ER' => 232,'EE' => 233,'ET' => 231,'FK' => 238,'FO' => 234,'FJ' => 242,
            'FI' => 246,'FR' => 250,'GF' => 254,'PF' => 258,'TF' => 260,'GA' => 266,'GM' => 270,'GE' => 268,'DE' => 276,'GH' => 288,
            'GI' => 292,'GR' => 300,'GL' => 304,'GD' => 308,'GP' => 312,'GU' => 316,'GT' => 320,'GG' => 831,'GN' => 324,'GW' => 624,
            'GY' => 328,'HT' => 332,'HM' => 334,'HN' => 340,'HK' => 344,'HU' => 348,'IS' => 352,'IN' => 356,'ID' => 360,'IR' => 364,
            'IQ' => 368,'IE' => 372,'IM' => 833,'IL' => 376,'IT' => 380,'JM' => 388,'JP' => 392,'JE' => 832,'JO' => 400,'KZ' => 398,
            'KE' => 404,'KI' => 296,'KW' => 414,'KG' => 417,'LA' => 418,'LV' => 428,'LB' => 422,'LS' => 426,'LR' => 430,'LY' => 434,
            'LI' => 438,'LT' => 440,'LU' => 442,'MO' => 446,'MK' => 807,'MG' => 450,'MW' => 454,'MY' => 458,'MV' => 462,'ML' => 466,
            'MT' => 470,'MH' => 584,'MQ' => 474,'MR' => 478,'MU' => 480,'YT' => 175,'MX' => 484,'FM' => 583,'MD' => 498,'MC' => 492,
            'MN' => 496,'ME' => 499,'MS' => 500,'MA' => 504,'MZ' => 508,'MM' => 104,'NA' => 516,'NR' => 520,'NP' => 524,'NL' => 528,
            'AN' => 530,'NC' => 540,'NZ' => 554,'NI' => 558,'NE' => 562,'NG' => 566,'NU' => 570,'NF' => 574,'MP' => 580,'KP' => 408,
            'NO' => 578,'OM' => 512,'PK' => 586,'PW' => 585,'PS' => 275,'PA' => 591,'PG' => 598,'PY' => 600,'PE' => 604,'PH' => 608,
            'PN' => 612,'PL' => 616,'PT' => 620,'PR' => 630,'QA' => 634,'RE' => 638,'RO' => 642,'RU' => 643,'RW' => 646,'BL' => '',
            'SH' => 654,'KN' => 659,'LC' => 662,'MF' => '','PM' => 666,'WS' => 882,'SM' => 674,'ST' => 678,'SA' => 682,'SN' => 686,
            'RS' => 688,'SC' => 690,'SL' => 694,'SG' => 702,'SK' => 703,'SI' => 705,'SB' => 90,'SO' => 706,'ZA' => 710,'GS' => 239,
            'KR' => 410,'ES' => 724,'LK' => 144,'VC' => 670,'SD' => 736,'SR' => 740,'SJ' => 744,'SZ' => 748,'SE' => 754,'CH' => 756,
            'SY' => 760,'TW' => 158,'TJ' => 762,'TZ' => 834,'TH' => 764,'TL' => 626,'TG' => 768,'TK' => 772,'TO' => 776,'TT' => 780,
            'TN' => 788,'TR' => 792,'TM' => 795,'TC' => 796,'TV' => 798,'UG' => 800,'UA' => 804,'AE' => 784,'GB' => 826,'US' => 840,
            'UY' => 858,'UM' => 581,'VI' => 850,'UZ' => 860,'VU' => 548,'VA' => 336,'VE' => 862,'VN' => 704,'WF' => 876,'EH' => 732,
            'YE' => 887,'ZM' => 894,'ZW' => 716,
        );
        return ($countryIds[$countryCode]) ? $countryIds[$countryCode] : 0;
    }
}