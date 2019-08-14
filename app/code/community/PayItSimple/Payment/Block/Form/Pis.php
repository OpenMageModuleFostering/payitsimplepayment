<?php

class PayItSimple_Payment_Block_Form_Pis extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payitsimple/form/pis.phtml');
    }

    public function getAvailableInstallments1()
    {
        $method = $this->getMethod();
        $installments = array();
        $totalAmount = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
        $options = Mage::getModel('pis_payment/source_installments')->toOptionArray();
        foreach (explode(',', $method->getConfigData('available_installments')) as $n) {
            if (isset($options[$n]['label'])) $installments[$n] = $options[$n]['label'] .' '. $this->__('of') . ' ' . $this->helper('checkout')->formatPrice(round($totalAmount/$n,2));
        }
        return $installments;
    }

    public function getAvailableInstallments()
    {
        $method = $this->getMethod();
        $installments = array();
        $totalAmount = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
        $selectInstallmentSetup = Mage::getStoreConfig('payment/pis_cc/select_installment_setup');
        $installmentsInDropdown = [];
        $options = Mage::getModel('pis_payment/source_installments')->toOptionArray();
        
        $depandOnCart = 0;
        // $selectInstallmentSetup == "" for checking when merchant first time upgrade extension that time $selectInstallmentSetup will be empty
        if($selectInstallmentSetup == "" || $selectInstallmentSetup == "fixed"){ // Select Fixed installment setup
            
            $fixedInstallments = Mage::getStoreConfig('payment/pis_cc/available_installments');
            foreach (explode(',', $fixedInstallments) as $n) {
                
                if((array_key_exists($n, $options))){
                    $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2); 
                    $installmentsInDropdown[$n] = round($totalAmount/$n,2); 
                }
            }
            
        }else{ // Select Depanding on cart installment setup
            $depandOnCart = 1;  
            $depandingOnCartInstallments = Mage::getStoreConfig('payment/pis_cc/depanding_on_cart_total_values');
            $depandingOnCartInstallmentsArr = json_decode($depandingOnCartInstallments);
            $dataAsPerCurrency = [];
            foreach($depandingOnCartInstallmentsArr as $data){
                $dataAsPerCurrency[$data->doctv->currency][] = $data->doctv;
            }
            $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            if(count($dataAsPerCurrency) && isset($dataAsPerCurrency[$currentCurrencyCode])){
                
                foreach($dataAsPerCurrency[$currentCurrencyCode] as $data){
                    if($totalAmount >= $data->from && !empty($data->to) && $totalAmount <= $data->to){
                        foreach (explode(',', $data->installments) as $n) {

                            if((array_key_exists($n, $options))){
                                $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);    
                                $installmentsInDropdown[$n] = round($totalAmount/$n,2);
                                                        
                            }
                        }
                        break;
                    }else if($totalAmount >= $data->from && empty($data->to)){
                        foreach (explode(',', $data->installments) as $n) {

                            if((array_key_exists($n, $options))){
                                $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);  
                                $installmentsInDropdown[$n] = round($totalAmount/$n,2); 
                                                         
                            }
                        }
                        break;
                    }
                }
            }
        } 



        if(count($installments) == 0){
            $installments[] = "Installments are not available.";
        }
        // set how much installments to be show in checkout page dropdown
        Mage::getSingleton('core/session')->setInstallmentsInDropdown($installmentsInDropdown);
        
        return $installments;
    }    

public function getAvailableInstallments_copy()
    {
        $method = $this->getMethod();
        $installments = array();
        $totalAmount = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
        $selectInstallmentSetup = Mage::getStoreConfig('payment/pis_cc/select_installment_setup');
        $installmentsInDropdown = [];
        $options = Mage::getModel('pis_payment/source_installments')->toOptionArray();
        $below100 = 0;
        $depandOnCart = 0;
        if($totalAmount < 100){
            $installments[] = "Splitit only support amount more than 100";
            $below100 = 1;
        }else{
            
            if($selectInstallmentSetup == "fixed"){ // Select Fixed installment setup
            
                $fixedInstallments = Mage::getStoreConfig('payment/pis_cc/available_installments');
                foreach (explode(',', $fixedInstallments) as $n) {
                    
                    if((array_key_exists($n, $options))){
                        $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2); 
                        $installmentsInDropdown[$n] = round($totalAmount/$n,2); 
                    }
                }
                
            }else{ // Select Depanding on cart installment setup
                    $depandOnCart = 1;  
                    $depandingOnCartInstallments = Mage::getStoreConfig('payment/pis_cc/depanding_on_cart_total_values');
                    $depandingOnCartInstallmentsArr = json_decode($depandingOnCartInstallments);
                    $dataAsPerCurrency = [];
                    foreach($depandingOnCartInstallmentsArr as $data){
                        $dataAsPerCurrency[$data->doctv->currency][] = $data->doctv;
                    }
                    $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
                    if(count($dataAsPerCurrency) && isset($dataAsPerCurrency[$currentCurrencyCode])){
                        
                        foreach($dataAsPerCurrency[$currentCurrencyCode] as $data){
                            if($totalAmount >= $data->from && !empty($data->to) && $totalAmount <= $data->to){
                                foreach (explode(',', $data->installments) as $n) {

                                    if((array_key_exists($n, $options))){
                                        $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);    
                                        $installmentsInDropdown[$n] = round($totalAmount/$n,2);
                                                                
                                    }
                                }
                                break;
                            }else if($totalAmount >= $data->from && empty($data->to)){
                                foreach (explode(',', $data->installments) as $n) {

                                    if((array_key_exists($n, $options))){
                                        $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);  
                                        $installmentsInDropdown[$n] = round($totalAmount/$n,2); 
                                                                 
                                    }
                                }
                                break;
                            }
                        }
                    }
                } 


        }   
        if($below100 == 0 && $depandOnCart == 1 && count($installments) == 0){
            $installments[] = "Installments are not available.";
        }
        // set how much installments to be show in checkout page dropdown
        Mage::getSingleton('core/session')->setInstallmentsInDropdown($installmentsInDropdown);
        
        return $installments;
    } 
    public function getMethodLabelAfterHtml(){
        $markFaq = Mage::getConfig()->getBlockClassName('core/template');
        $markFaq = new $markFaq;
        $markFaq->setTemplate('payitsimple/form/method_faq.phtml')
            ->setPaymentInfoEnabled($this->getMethod()->getConfigData('faq_link_enabled'))
            ->setPaymentInfoTitle($this->getMethod()->getConfigData('faq_link_title'));
        return $markFaq->toHtml();
    }
}
