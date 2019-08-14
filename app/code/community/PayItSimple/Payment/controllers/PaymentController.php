<?php

class PayItSimple_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function helpAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function termsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function apiLoginAction(){
        
        $storeId = Mage::app()->getStore()->getStoreId();
        $api = Mage::getSingleton("pis_payment/pisMethod")->_initApi($storeId = null);
        
        $installmentsInDropdown = [];
        $response = [
                        "status" => false,
                        "error" => "",
                        "success"=>"",
                        "data" => "",
                        "installmentNum" => "1",

        ];
        /*if ($api->isLogin()){

            $result = Mage::getSingleton("pis_payment/pisMethod")->getValidNumberOfInstallments($api);
            $responseData = json_decode($result);
            if(!empty($responseData) && isset($responseData->ResponseHeader) && $responseData->ResponseHeader){

                $selectInstallmentSetup = Mage::getStoreConfig('payment/pis_cc/select_installment_setup');
                $totalAmount = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
                $options = $responseData->ValidNumberOfInstallments;
                //$options = Mage::getModel('pis_payment/source_installments')->toOptionArray();
                $installments = [];
                if($totalAmount < 100){
                    $installments[] = "Splitit only support amount more than 100";
                    $response["installmentNum"] = 0;
                }else{
                    if($selectInstallmentSetup == "fixed"){ // Select Fixed installment setup
                    
                        $fixedInstallments = Mage::getStoreConfig('payment/pis_cc/fixed_installment');
                        foreach (explode(',', $fixedInstallments) as $n) {

                            if(in_array($n, $options)){
                                $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);  
                                $installmentsInDropdown[$n] = round($totalAmount/$n,2);                           
                            }
                        }
                        
                    }else{ // Select Depanding on cart installment setup
                        
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

                                        if(in_array($n, $options)){
                                            $installments[$n] = $n.' Installments of '. Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().round($totalAmount/$n,2);    
                                            $installmentsInDropdown[$n] = round($totalAmount/$n,2);                        
                                        }
                                    }
                                    break;
                                }else if($totalAmount >= $data->from && empty($data->to)){
                                    foreach (explode(',', $data->installments) as $n) {

                                        if(in_array($n, $options)){
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
                
                // set how much installments to be show in checkout page dropdown
                Mage::getSingleton('core/session')->setInstallmentsInDropdown($installmentsInDropdown);

                $response["data"] = $installments;
                $response["status"] = true;
            }else if(!empty($responseData) && isset($responseData->ResponseStatus)){
                $response["error"] = $responseData->ResponseStatus->Message;
            }     
            
        }else{
            $response["error"] = $api->getError();
        }*/
        if ($api->isLogin()){
            $response["status"] = true;
        }else{
            foreach ($api->getError() as $key => $value) {
                $response["error"] .= $value." ";    
            }
            
        }
        echo $jsonData = Mage::helper('core')->jsonEncode($response);
        //echo $jsonData = json_encode($response);
        return ;
        
    }

    public function installmentplaninitAction(){

        $params = $this->getRequest()->getParams();
        $selectedInstallment = "";
        $response = [
                        "status" => false,
                        "error" => "",
                        "success"=>"",
                        "data" => "",
                        
        ];
        if(isset($params["selectedInstallment"])){
            $selectedInstallment = $params["selectedInstallment"];
        }
        if($selectedInstallment == ""){
            $response["data"] = "Please select Number of Installments";
            echo $jsonData = Mage::helper('core')->jsonEncode($response);
            return ;
        }
        $api = Mage::getSingleton("pis_payment/pisMethod");
        $splititSessionId = Mage::getSingleton('core/session')->getSplititSessionid();

        if ($splititSessionId != ""){
            $result = Mage::getSingleton("pis_payment/pisMethod")->installmentplaninit($api, $selectedInstallment);
            $response["data"] = $result["data"];
            if($result["status"]){
                $response["status"] = true;
            }
            if(isset($result["emptyFields"]) && $result["emptyFields"]){
                $response["data"] = $result["data"];    
            }
            
        }else{
            
            $response["data"] = "703 - Session is not valid";
        }
        

        
        echo $jsonData = Mage::helper('core')->jsonEncode($response);   
        return ;
    }
}