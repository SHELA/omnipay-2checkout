<?php

namespace Omnipay\TwoCheckoutPlus\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Message\AbstractResponse;

class NotificationResponse extends AbstractResponse implements NotificationInterface
{
    const ORDER_CREATED = 'ORDER_CREATED';
    const FRAUD_STATUS_CHANGED = 'FRAUD_STATUS_CHANGED';
    const INVOICE_STATUS_CHANGED = 'INVOICE_STATUS_CHANGED';
    const REFUND_ISSUED = 'REFUND_ISSUED';
    //Order Status Values:
    const ORDER_STATUS_PENDING = 'PENDING';
    const ORDER_STATUS_PAYMENT_AUTHORIZED = 'PAYMENT_AUTHORIZED';
    const ORDER_STATUS_SUSPECT = 'SUSPECT';
    const ORDER_STATUS_INVALID = 'INVALID';
    const ORDER_STATUS_COMPLETE = 'COMPLETE';
    const ORDER_STATUS_REFUND = 'REFUND';
    const ORDER_STATUS_REVERSED = 'REVERSED';
    const ORDER_STATUS_PURCHASE_PENDING = 'PURCHASE_PENDING';
    const ORDER_STATUS_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    const ORDER_STATUS_CANCELED = 'CANCELED';
    const ORDER_STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const FRAUD_STATUS_APPROVED = 'APPROVED';
    const FRAUD_STATUS_DENIED = 'DENIED';
    const FRAUD_STATUS_REVIEW = 'UNDER REVIEW';
    const FRAUD_STATUS_PENDING = 'PENDING';
    const PAYMENT_METHOD = 'tco_checkout';

    /**
     * Is the notification harsh correct after validation?
     */
    public function isSuccessful()
    {
       return $this->data['ORDERSTATUS'] === self::ORDER_STATUS_COMPLETE;
//        if (!empty($orderStatus)) {
//            switch (trim($orderStatus)) {
//                case self::ORDER_STATUS_PENDING:
//                case self::ORDER_STATUS_PURCHASE_PENDING:
//                    if(!$this->_isOrderCompleted())
//                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
//                    break;
//                case self::ORDER_STATUS_PENDING_APPROVAL:
//                case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
//                    if(!$this->_isOrderCompleted()) {
//                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
//                    }
//                    break;
//
//                case self::ORDER_STATUS_COMPLETE:
//                    $this->order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
//                    $this->_createTransactionId($params);
//                    break;
//
//                case self::ORDER_STATUS_REFUND:
//                    $this->order->setCurrentState(Configuration::get('PS_OS_REFUND'));
//                    break;
//
//                default:
//                    throw new Exception('Cannot handle Ipn message type for message');
//            }
//
//        }
    }

    /**
     * 2Checkout transaction reference.
     *
     * @return mixed
     */
    public function getTransactionReference()
    {
        return $this->data;
    }

    /**
     * Order or transaction ID.
     *
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->data['REFNO'];
    }

    /**
     * Get transaction/notification status.
     *
     * SInce this is an IPN notification, we made this true.
     *
     * @return bool
     */
    public function getTransactionStatus()
    {
        return true;
    }

    /**
     * Notification response.
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->data;
    }

    public function getOrderId(){
        return $this->data['REFNOEXT'];
    }

    public function getTotal(){
        return $this->data['IPN_TOTALGENERAL'];
    }

    public function getFraud(){
        return $this->_isFraud();
    }

    public function verify()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            die;
        }

        if (!isset($this->data['REFNOEXT']) && (!isset($this->data['REFNO']) && empty($this->data['REFNO']))) {
            throw new \Exception(sprintf('Cannot identify order: "%s".',
                $this->data['REFNOEXT']));
        }

        if (!$this->isIpnResponseValid($this->data, $this->data['secretKey'])) {
            throw new \Exception(sprintf('MD5 hash mismatch for 2Checkout IPN with date: "%s".',
                $this->data['IPN_DATE']));
        }

        return true;
    }

    public function response(){
        echo $this->_calculateIpnResponse(
            $this->data,
            $this->data['secretKey']
        );
        die();
    }

    protected function _isFraud()
    {
        return (isset($this->data['FRAUD_STATUS']) && $this->data['FRAUD_STATUS'] === self::FRAUD_STATUS_DENIED);
    }

    /**
     * @param $params
     * @param $secretKey
     *
     * @return bool
     */
    public function isIpnResponseValid($params, $secretKey)
    {
        $result = '';
        $receivedHash = $params['HASH'];
        unset($params['secretKey']);
        unset($params['accountNumber']);
        foreach ($params as $key => $val) {

            if ($key != "HASH") {
                if (is_array($val)) {
                    $result .= $this->arrayExpand($val);
                } else {
                    $size = strlen(stripslashes($val));
                    $result .= $size . stripslashes($val);
                }
            }
        }

        if (isset($params['REFNO']) && !empty($params['REFNO'])) {
            $calcHash = $this->hmac($secretKey, $result);
            if ($receivedHash === $calcHash) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $ipnParams
     * @param $secret_key
     *
     * @return string
     */
    private function _calculateIpnResponse($ipnParams, $secret_key)
    {
        $resultResponse = '';
        $ipnParamsResponse = [];
        // we're assuming that these always exist, if they don't then the problem is on avangate side
        $ipnParamsResponse['IPN_PID'][0] = $ipnParams['IPN_PID'][0];
        $ipnParamsResponse['IPN_PNAME'][0] = $ipnParams['IPN_PNAME'][0];
        $ipnParamsResponse['IPN_DATE'] = $ipnParams['IPN_DATE'];
        $ipnParamsResponse['DATE'] = date('YmdHis');

        foreach ($ipnParamsResponse as $key => $val) {
            $resultResponse .= $this->arrayExpand((array)$val);
        }

        return sprintf(
            '<EPAYMENT>%s|%s</EPAYMENT>',
            $ipnParamsResponse['DATE'],
            $this->hmac($secret_key, $resultResponse)
        );
    }

    /**
     * @param $array
     *
     * @return string
     */
    private function arrayExpand($array)
    {
        $retval = '';
        foreach ($array as $key => $value) {
            $size = strlen(stripslashes($value));
            $retval .= $size . stripslashes($value);
        }
        return $retval;
    }

    /**
     * @param $key
     * @param $data
     *
     * @return string
     */
    private function hmac($key, $data)
    {
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    /**
     * @param $params
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    private function _processOrderStatus($params)
    {
        $orderStatus = $params['ORDERSTATUS'];
        if (!empty($orderStatus)) {
            switch (trim($orderStatus)) {
                case self::ORDER_STATUS_PENDING:
                case self::ORDER_STATUS_PURCHASE_PENDING:
                    if(!$this->_isOrderCompleted())
                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
                    break;
                case self::ORDER_STATUS_PENDING_APPROVAL:
                case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
                    if(!$this->_isOrderCompleted()) {
                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
                    }
                    break;

                case self::ORDER_STATUS_COMPLETE:
                    $this->order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                    $this->_createTransactionId($params);
                    break;

                case self::ORDER_STATUS_REFUND:
                    $this->order->setCurrentState(Configuration::get('PS_OS_REFUND'));
                    break;

                default:
                    throw new Exception('Cannot handle Ipn message type for message');
            }

            $this->order->save();
        }
    }

    private function _isOrderCompleted(){
        return $this->order->getCurrentOrderState() == Configuration::get('PS_OS_PAYMENT');
    }

}
