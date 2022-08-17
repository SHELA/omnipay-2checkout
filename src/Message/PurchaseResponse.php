<?php

namespace Omnipay\TwoCheckoutPlus\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\TwoCheckoutPlus\helpers\Twocheckout_Convert_Plus_Ipn_Helper;

/**
 * Response.
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $liveEndpoint = 'https://secure.2checkout.com/checkout/buy';
    private $_signParams = [
        'return-url',
        'return-type',
        'expiration',
        'order-ext-ref',
        'item-ext-ref',
        'lock',
        'cust-params',
        'customer-ref',
        'customer-ext-ref',
        'currency',
        'prod',
        'price',
        'qty',
        'tangible',
        'type',
        'opt',
        'coupon',
        'description',
        'recurrence',
        'duration',
        'renewal-price',
    ];
    /**
     * Get appropriate 2checkout endpoints.
     *
     * @return string
     */
    public function getEndPoint()
    {
        return $this->liveEndpoint;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        $endpoint = $this->getEndPoint();

        $data = array_merge(
            $this->data['setup_data'],
            $this->data['cart_data'],
            $this->data['product_data'],
            $this->data['billing_data']
        );
        $signature = $this->generateSignature($data, $this->data['setup_data']['secret_word']);
        $buy_link_params = $data;
        $buy_link_params['signature'] = $signature;
        unset($buy_link_params['secret_word']);

        $url = $endpoint.'?'.http_build_query($buy_link_params);
        return str_replace('&amp;', '&', $url);
    }

    /**
     * @return string
     */
    public function getRedirectMethod()
    {
        return 'GET';
    }

    /**
     * No redirect data.
     */
    public function getRedirectData()
    {
        return;
    }

    private function generateSignature(
        $params,
        $secretWord,
        $fromResponse = false
    ) {

        if (!$fromResponse) {
            $signParams = array_filter($params, function ($k) {
                return in_array($k, $this->_signParams);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $signParams = $params;
            if (isset($signParams['signature'])) {
                unset($signParams['signature']);
            }
        }

        ksort($signParams); // order by key
        // Generate Hash
        $string = '';
        foreach ($signParams as $key => $value) {
            $string .= strlen($value) . $value;
        }

        return bin2hex(hash_hmac('sha256', $string, $secretWord, true));
    }
}
