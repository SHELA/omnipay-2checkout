<?php

namespace Omnipay\TwoCheckoutPlus\Message;

use Guzzle\Http\Exception\BadResponseException;
use Omnipay\Common\Http\Exception\NetworkException;
use Omnipay\Common\Http\Exception\RequestException;
use Omnipay\TwoCheckoutPlus\TwoCheckoutApi;

/**
 * Purchase Request.
 *
 * @method PurchaseResponse send()
 */
class RefundRequest extends AbstractRequest
{

    public function getData()
    {
        $this->validate('accountNumber', 'secretKey');

        $data = [];
        $data['accountNumber'] = $this->getAccountNumber();
        $data['secretKey'] = $this->getSecretKey();

        $data['transaction_id'] = $this->getTransactionId();
        $data['amount'] = $this->getParameter('amount');
        $data['comment'] = 'Buyer deserved a refund';

        // override default comment
        if ($this->getComment()) {
            $data['comment'] = $this->getComment();
        }

        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        return $data;
    }


    /**
     * @param mixed $data
     *
     * @return RefundResponse
     */
    public function sendData($data)
    {

        $api = new TwoCheckoutApi();
        $api->set_seller_id( $data['accountNumber'] );
        $api->set_secret_key( $data['secretKey'] );
        echo "aa";
        $tco_order = $api->call( 'orders/' . $data['transaction_id'] . '/', [], 'GET' );
        print_r($tco_order);
        if ( ! $tco_order || ! $data['transaction_id'] ) {
            throw new \Exception('Order Or Transaction not found');
        }

        try {
            $params = [
                "amount"  => $data['amount'],
                "comment" => $data['comment'],
                "reason"  => 'Other'
            ];

            $response = $api->call( '/orders/' . $data['transaction_id'] . '/refund/', $params, 'POST' );
print_r($response);
//            if ( isset( $response['error_code'] ) && ! empty( $response['error_code'] ) ) {
            return new RefundResponse($this, $json ?? []);
        } catch (RequestException|NetworkException $e) {
            return new RefundResponse($this, ['error' => $e->getMessage()]);
        }
    }
}
