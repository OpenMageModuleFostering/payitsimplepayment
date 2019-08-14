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
        $result = $this->makeRequest($gwUrl, ucfirst(__FUNCTION__), $params);
        if ($result) {
            $this->_sessionId = (isset($result['SessionId']) && $result['SessionId'] != '') ? $result['SessionId'] : null;
            if (is_null($this->_sessionId)){
                $this->setError(self::ERROR_UNKNOWN, 'Unable get API SessionId');
                return false;
            }
            $this->_gwUrl = $gwUrl;
            $this->_apiTerminalKey = $params['ApiKey'];
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isLogin(){
        return (!is_null($this->_sessionId));
    }

    /**
     * @param array $params
     *
     * @return array|bool
     */
    public function createInstallmentPlan(array $params)
    {
        if (!$this->isLogin()) {
            $this->setError(self::ERROR_UNKNOWN, __FUNCTION__ . ' method required Login action first.');
            return false;
        }
        return $this->makeRequest($this->_gwUrl, ucfirst(__FUNCTION__), array_merge($params, array('ApiKey' => $this->_apiTerminalKey, 'SessionId' => $this->_sessionId)));
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
                throw new ErrorException($this->getGatewayError((int)$result['Result']) . $result['ResponseStatus'], (int)$result['Result']);
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
            'timeout'      => 30));
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

    public function getInstallmentPlanStatusList()
    {
        return array(
            1 => 'Pending Terms and Conditions approval',
            2 => 'Pending for Shipping',
            3 => 'In process',
            4 => 'Installment plan finished',
            5 => 'Plan cancelled by the customer (during the wizard)',
            6 => 'Installment plan finished and cleared by PayItSimple',
            7 => 'Pending customer credit card replacement',
            8 => 'Plan frozen (only authorizations continues)',
            9 => 'Plan cancelled by the merchant or by PayitSimple',
        );
    }

    public function getCcTypesAvailable()
    {
        return array(
            'MC' => 1,
            'VI' => 2,
        );
    }

    public function getGatewayError($code = null)
    {
        $errors = array(
            0 => 'The operation completed successfully',
            4 => 'The operation was denied',
            501 => 'Invalid credentials',
            502 => 'Invalid installment plan number',
            503 => 'Invalid installment plan status',
            504 => 'Invalid card type',
            505 => 'Invalid number of installments',
            506 => 'Invalid amount format',
            508 => 'Invalid country code',
            509 => 'Invalid response URL',
            510 => 'Invalid cardholder name format',
            511 => 'This amount is not allowed',
            520 => 'Invalid CVV (3-4 numbers)',
            521 => 'Invalid card number (8-20 numbers)',
            522 => 'Invalid expiration date (m=1-12,y=current year or higher)',
            523 => 'Invalid consumer full name (min 2 words + min 2 characters for each word)',
            524 => 'Invalid email (email format)',
            525 => 'Invalid address (2-50 characters)',
            526 => 'Invalid ZIP code (2-9 characters)',
            599 => 'General error',
        );
        return (is_null($code)) ? $errors : $errors[$code];
    }

}