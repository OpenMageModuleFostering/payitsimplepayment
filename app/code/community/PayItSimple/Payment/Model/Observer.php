<?php
class PayItSimple_Payment_Model_Observer
{
    public function insertBlock($observer)
    {
        $_block = $observer->getBlock();     
        $_type = $_block->getType();
        if (($_type == 'catalog/product_price' && $_block->getTemplate()=='catalog/product/price.phtml') or $_type == 'checkout/cart_totals') {
            $_child = clone $_block;
            $_child->setType('payitsimple/block');
            if($_type == 'checkout/cart_totals'){
                $_block->setChild('child', $_child);
            }
            else{
                $_block->setChild('child'.$_child->getProduct()->getId(), $_child);
            }
            $_block->setTemplate('payitsimple/splitprice.phtml');
        }
    }

    public function paymentMethodIsActive(Varien_Event_Observer $observer) {
        
        $event           = $observer->getEvent();//print_r($event->getData());die("---sdf");
        $method          = $event->getMethodInstance();
        $result          = $event->getResult();
        $currencyCode    = Mage::app()->getStore()->getCurrentCurrencyCode();


            if($method->getCode() == "pis_cc"){
                $result->isAvailable = $this->checkAvailableInstallments();
            }/*else{
                $result->isAvailable = false;
            }*/

    }

    private function checkAvailableInstallments(){
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
            return false;
        }else{
            return true;
        }
    }
}
