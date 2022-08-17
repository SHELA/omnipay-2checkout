<?php

namespace Omnipay\TwoCheckoutPlus\Message;

/**
 * Purchase Request.
 *
 * @method PurchaseResponse send()
 */
class PurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('accountNumber', 'returnUrl');

        $setup_data             = [];
        $setup_data['merchant'] = $this->getAccountNumber();
        $setup_data['secret_word'] = $this->getSecretWord();
        $setup_data['dynamic']  = 1;
        $setup_data['language'] = 'en';
        //2. Set the BASE needed fields.
        $cart_data                     = [];
        $cart_data['return-url']       = $this->getReturnUrl();
        $cart_data['return-type']      = 'redirect';
        $cart_data['expiration']       = time() + ( 3600 * 5 );
        $cart_data['order-ext-ref']    = $this->getTransactionId();
        $cart_data['customer-ext-ref'] = $this->getCustomerId();
        $cart_data['item-ext-ref']     = $this->getProductId();
        $cart_data['currency']         = $this->getCurrency();
        $cart_data["test"]             = $this->getTestMode();

        //dynamic products
        $product_data['prod']     = $this->getDescription();
        $product_data['price']    = $this->getAmount();
        $product_data['qty']      = $this->getQty();
        $product_data['type']     = "digital";

        if ($this->getLanguage()) {
            $cart_data['lang'] = $this->getLanguage();
        }
        $billing_data = [];
        if ($this->getCard()) {
            $billing_data['name'] = $this->getCard()->getName();
            $billing_data['address'] = $this->getCard()->getAddress1();
            $billing_data['address2'] = $this->getCard()->getAddress2();
            $billing_data['city'] = $this->getCard()->getCity();
            $billing_data['state'] = $this->getCard()->getState();
            $billing_data['zip'] = $this->getCard()->getPostcode();
            $billing_data['country'] = $this->getCard()->getCountry();
            $billing_data['phone'] = $this->getCard()->getPhone();
            $billing_data['email'] = $this->getCard()->getEmail();
        }

        $data = [
            'setup_data'    => $setup_data,
            'cart_data'     => $cart_data,
            'product_data'  => $product_data,
            'billing_data'  => $billing_data,
        ];

        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        return $data;
    }

    /**
     * @param mixed $data
     *
     * @return PurchaseResponse
     */
    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
