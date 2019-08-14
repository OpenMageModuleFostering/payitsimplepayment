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
            6 => 'Installment plan finished and cleared by SplitIt',
            7 => 'Pending customer credit card replacement',
            8 => 'Plan frozen (only authorizations continues)',
            9 => 'Plan cancelled by the merchant or by SplitIt',
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
            501 => 'Invalid Credentials',
            502 => 'Invalid Installment Plan Number',
            503 => 'Invalid Installment Plan Status',
            504 => 'Card type not supported',
            505 => 'Invalid Number of Installments',
            506 => 'Invalid Amount Format',
            508 => 'Invalid Country Code',
            509 => 'Invalid Response URL',
            510 => 'Invalid Card holder Name',
            511 => 'Invalid Amount',
            520 => 'Invalid CVV',
            521 => 'Invalid Card Number',
            522 => 'Invalid Expiration Date ',
            523 => 'Invalid Consumer Full Name ',
            524 => 'Invalid Email Format',
            525 => 'Invalid Address ',
            526 => 'Invalid ZIP Code ',
            527 => 'Card bin not supported ',
            528 => 'Card issue country not supported',
            599 => 'General Error Occurred',
            600 => 'Gateway failed to process request',
            601 => 'Invalid ZIP Code ',
            602 => 'Invalid Address ',
            603 => 'Invalid CVV',
            604 => 'Invalid Expiration Date ',
            606 => 'Invalid Address or Zip',
            607 => 'Invalid card number',
            608 => 'Problem with card',
            609 => 'Insufficient funds',
            610 => 'Credit card was declined',
            630 => 'Failed to connect to gateway',
            640 => 'Problem with merchant details on gateway',
        );
        return (is_null($code)) ? $errors : $errors[$code];
    }
}