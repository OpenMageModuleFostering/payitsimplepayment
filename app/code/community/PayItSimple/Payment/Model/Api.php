<?php

class PayItSimple_Payment_Model_Api extends Mage_Core_Model_Abstract
{
    const ERROR_HTTP_STATUS_CODE = -2;
    const ERROR_HTTP_REQUEST = -4;
    const ERROR_JSON_RESPONSE = -8;
    const ERROR_UNKNOWN_GW_RESULT_CODE = -16;
    const ERROR_UNKNOWN = -32;

    protected $_error = array();
    protected $_sessionId = null;
    protected $_apiTerminalKey = null;
    protected $_gwUrl = null;


    /**
     * @param $gwUrl
     * @param $params
     *
     * @return bool|array
     */
    public function login($gwUrl, $params){
        
        //$result = $this->makeRequest($gwUrl, ucfirst(__FUNCTION__), $params);
        
        $result =  $this->makePhpCurlRequest($gwUrl, ucfirst(__FUNCTION__), $params); 
        $result = Mage::helper('core')->jsonDecode($result);
        if ($result) {
            $this->_sessionId = (isset($result['SessionId']) && $result['SessionId'] != '') ? $result['SessionId'] : null;
            if (is_null($this->_sessionId)){
                if(isset($result["serverError"])){
                    $this->getError();
                }else{
                    $gatewayErrorCode = $result["ResponseHeader"]["Errors"][0]["ErrorCode"];
                    $gatewayErrorMsg = $result["ResponseHeader"]["Errors"][0]["Message"];

                    $this->setError($gatewayErrorCode, $gatewayErrorMsg);    
                }
                
                return false;
            }
            $this->_gwUrl = $gwUrl;
            $this->_apiTerminalKey = $params['ApiKey'];
            // set Splitit session id into session

            Mage::getSingleton('core/session')->setSplititSessionid($this->_sessionId);
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isLogin(){
        return (!is_null($this->_sessionId));
    }

    
    public function createInstallmentPlan($url, array $params)
    {
        if (Mage::getSingleton('core/session')->getSplititSessionid() == "") {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }

        return $this->makePhpCurlRequest($url, "InstallmentPlan/Create",$params);
    }

    public function startInstallment($url, array $params){
        if (Mage::getSingleton('core/session')->getSplititSessionid() == "") {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }
        

        return $this->makePhpCurlRequest($url, "InstallmentPlan/StartInstallments",$params);    
    }

    /**
     * @param $params
     *
     * @return bool|array
     */
    public function notifyOrderShipped(array $params)
    {
        if (!$this->isLogin()) {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }
        return $this->makeRequest($this->_gwUrl, ucfirst(__FUNCTION__), array_merge($params, array('ApiKey' => $this->_apiTerminalKey, 'SessionId' => $this->_sessionId)));
    }

    /**
     * @param array $params
     *
     * @return array|bool
     */
    public function updateInstallmentPlan(array $params)
    {
        if (!$this->isLogin()) {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }
        return $this->makeRequest($this->_gwUrl, ucfirst(__FUNCTION__), array_merge($params, array('ApiKey' => $this->_apiTerminalKey, 'SessionId' => $this->_sessionId)));
    }

    public function updateRefOrderNumber($apiUrl, $params){
        try{
            return $this->makePhpCurlRequest($apiUrl, "InstallmentPlan/Update" , $params);        
        }catch(Exception $e){
            echo $e->getMessage();
        } 
    }

    /**
     * @param $gwUrl string
     * @param $method string
     * @param $params array
     *
     * @return bool|array
     */
    protected function makeRequest($gwUrl, $method, $params)
    {
        $this->_error = array();
        $result = false;
        try {
            $client = $this->getHttpClient($gwUrl, $method, $params);
            $response = $client->request();
            $this->setData('request', $this->secureFilter($client->getLastRequest()));
            $this->setData('response', $response->getHeadersAsString() . $response->getBody());
            if (!$response->isSuccessful()) {
                throw new ErrorException('Response from gateway is not successful. HTTP Code: '. $response->getStatus(), self::ERROR_HTTP_STATUS_CODE);
            }
            $result = Zend_Json::decode($response->getBody());
            if (!isset($result['Result'])) {
                throw new ErrorException('Unknown result from gateway.', self::ERROR_UNKNOWN_GW_RESULT_CODE);
            } elseif ($result['Result'] != 0) {
                //throw new ErrorException($this->getGatewayError((int)$result['Result']) . $result['ResponseStatus'], (int)$result['Result']);
                throw new ErrorException($result['ResponseStatus']['Message']."\n(".$result['ErrorAdditionalInfo'].")", (int)$result['Result']);
            }
        } catch (Zend_Http_Client_Exception $e) {
            $this->setError(self::ERROR_HTTP_REQUEST, $e->getMessage());
        } catch (Zend_Json_Exception $e) {
            $this->setError(self::ERROR_JSON_RESPONSE, $e->getMessage());
        } catch (ErrorException $e) {
            $result = false;
            $this->setError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    protected function secureFilter($str)
    {
        $patterns = array('/(CardNumber=\d{4})(\d+)/','/(CardCvv=)(\d+)/');
        return preg_replace($patterns, '${1}***', $str);
    }

    /**
     * @param $url string
     * @param $method string
     * @param $params array
     *
     * @return Zend_Http_Client
     * @throws Zend_Http_Client_Exception
     */
    protected function getHttpClient($url, $method, $params)
    {
        $client = new Zend_Http_Client(trim($url,'/') . '/api/' . $method . '?format=JSON');
        $client->setConfig(array(
            'maxredirects' => 0,
            'timeout'      => 30,
            'curloptions' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $client->setMethod(Zend_Http_Client::POST);
        $client->setParameterPost($params);
        return $client;
    }

    /**
     * @return array
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @param $errorCode int
     * @param $errorMsg string
     */
    protected function setError($errorCode, $errorMsg)
    {
        $this->_error = array('code' => $errorCode, 'message' => $errorMsg);
    }


    public function getCcTypesAvailable()
    {
        return array(
            'MC' => 1,
            'VI' => 2,
            /*'AE' => 3,
            'DI' => 4,
            'OT' => 5,*/
        );
    }


    public function getValidNumberOfInstallments(){
        if (!$this->isLogin()) {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }
        $arr =  array("RequestHeader"=>array('ApiKey' => $this->_apiTerminalKey, 'SessionId' => trim($this->_sessionId)));
        
        //return $this->makeRequest($this->_gwUrl, "InstallmentPlan/GetValidNumberOfInstallments" , $arr);
        return $this->makePhpCurlRequest($this->_gwUrl, "InstallmentPlan/GetValidNumberOfInstallments" , $arr);

       
    }

    public function installmentplaninit($apiUrl, $params){
        try{
            return $this->makePhpCurlRequest($apiUrl, "InstallmentPlan/Initiate" , $params);        
        }catch(Exception $e){
            echo $e->getMessage();
        } 
        
    }

    public function getApprovalUrlResponse($approvalUrl){
        $url = $approvalUrl . '&format=json';
        $ch = curl_init($url);
        $jsonData = json_encode($params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonData);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',                                                                                
            'Content-Length:' . strlen($jsonData))                                                                       
        );
        $result = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // check for curl error eg: splitit server down.
        if(curl_errno($ch)){
            //echo 'Curl error: ' . curl_error($ch);
            $result["serverError"] = $this->getServerDownMsg();
            return $result = Mage::helper('core')->jsonEncode($result);
        }
        curl_close($ch);
        return $result;
    }   

    public function makePhpCurlRequest($gwUrl, $method, $params){
        $url = trim($gwUrl,'/') . '/api/' . $method . '?format=JSON';
        $ch = curl_init($url);
        $jsonData = json_encode($params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonData);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',                                                                                
            'Content-Length:' . strlen($jsonData))                                                                       
        );
        $result = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // check for curl error eg: splitit server down.
        if(curl_errno($ch)){
            //echo 'Curl error: ' . curl_error($ch);
            $this->setError($code, $this->getServerDownMsg());
            curl_close($ch);
            $result["serverError"] = $this->getServerDownMsg();
            return $result = Mage::helper('core')->jsonEncode($result);
        }
        curl_close($ch);
        return $result;
    }

    public function getServerDownMsg(){
        return "Failed to connect to splitit payment server. Please retry again later.";
    }
}