<?php

namespace Payum\PostfinanceDirectLink;

use GuzzleHttp\Client;
use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use PostFinance\DirectLink\DirectLinkMaintenanceRequest;
use PostFinance\DirectLink\DirectLinkMaintenanceResponse;
use PostFinance\DirectLink\DirectLinkPaymentRequest;
use PostFinance\DirectLink\DirectLinkPaymentResponse;
use PostFinance\ParameterFilter\ShaInParameterFilter;
use PostFinance\Passphrase;
use PostFinance\ShaComposer\AllParametersShaComposer;

class Api
{
    const TEST = 'test';
    const PRODUCTION = 'production';

    protected $options = [
        'sha-in-passphrase' => null,
        'pspid' => null,
        'user' => null,
        'password' => null,
        'environment' => self::TEST,
    ];

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @param array $options
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function createTransaction(array $fields)
    {
        $passphrase = new Passphrase($this->options['sha-in-passphrase']);
        $shaComposer = new AllParametersShaComposer($passphrase);
        $shaComposer->addParameterFilter(new ShaInParameterFilter()); //optional

        $directLinkRequest = new DirectLinkPaymentRequest($shaComposer);

        if ($this->options['environment'] === self::PRODUCTION) {
            $directLinkRequest->setPostFinanceUri(DirectLinkPaymentRequest::PRODUCTION);
        } else {
            $directLinkRequest->setPostFinanceUri(DirectLinkPaymentRequest::TEST);
        }

        $directLinkRequest->setOrderid($fields['orderid']);
        $directLinkRequest->setPspid($this->options['pspid']);
        $directLinkRequest->setUserId($this->options['user']);
        $directLinkRequest->setPassword($this->options['password']);
        $directLinkRequest->setAmount($fields['amount']);
        $directLinkRequest->setCurrency($fields['currency']);

        if (!isset($fields['operation'])) {
            $fields['operation'] = DirectLinkPaymentRequest::OPERATION_REQUEST_AUTHORIZATION;
        }

        $directLinkRequest->setOperation($fields['operation']);
        $directLinkRequest->setBrand($fields['brand']);
        $directLinkRequest->setEd($fields['ed']);
        $directLinkRequest->setCvc($fields['cvc']);
        $directLinkRequest->setCardno($fields['cardno']);
        $directLinkRequest->validate();

        // remove critical data from payment
        $fields['cardno'] = str_repeat("X", strlen($fields['cardno']));
        $fields['cvc'] = str_repeat("X", strlen($fields['cvc']));
        $fields['ed'] = str_repeat("X", strlen($fields['ed']));

        $request = $this->messageFactory->createRequest(
            'POST',
            $directLinkRequest->getPostFinanceUri(),
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($directLinkRequest->toArray())
        );

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $directLinkResponse = new DirectLinkPaymentResponse($response->getBody()->getContents());

        return array_merge($fields, $directLinkResponse->toArray());
    }

    /**
     * @param array $fields
     */
    public function captureTransaction(array $fields)
    {
        $passphrase = new Passphrase($this->options['sha-in-passphrase']);
        $shaComposer = new AllParametersShaComposer($passphrase);
        $shaComposer->addParameterFilter(new ShaInParameterFilter()); //optional

        $directLinkRequest = new DirectLinkMaintenanceRequest($shaComposer);
        $directLinkRequest->setPspid($this->options['pspid']);
        $directLinkRequest->setUserId($this->options['user']);
        $directLinkRequest->setPassword($this->options['password']);
        $directLinkRequest->setPayId($fields['PAYID']);

        if ($fields['amount'] > 0) {
            $operation = DirectLinkMaintenanceRequest::OPERATION_CAPTURE_LAST_OR_FULL;
        } else {
            $operation = DirectLinkMaintenanceRequest::OPERATION_AUTHORISATION_DELETE_AND_CLOSE;
        }

        $directLinkRequest->setOperation($operation);
        $directLinkRequest->validate();

        $request = $this->messageFactory->createRequest(
            'POST',
            $directLinkRequest->getPostFinanceUri(),
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($directLinkRequest->toArray())
        );

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $directLinkResponse = new DirectLinkMaintenanceResponse($response->getBody()->getContents());

        return array_merge($fields, $directLinkResponse->toArray());
    }

    /*
    /**
     * @param array $fields
     *
     * @return array
     *
    public function createTransaction(array $fields)
    {

        $fields = (array_replace([
            'success_url' => null,
            'success_link_redirect' => true,
            'abort_url' => null,
            'notification_url' => null,
            'notify_on' => implode(',', [self::STATUS_PENDING, self::STATUS_LOSS, self::STATUS_RECEIVED, self::STATUS_REFUNDED, self::STATUS_UNTRACEABLE]),
            'reason' => '',
            'reason_2' => '',
            'product_code' => null,
        ], $fields));

        $sofort = new Sofortueberweisung($this->options['config_key']);
        $sofort->setAmount($fields['amount']);
        $sofort->setCurrencyCode($fields['currency_code']);
        $sofort->setReason($fields['reason'], $fields['reason_2'], $fields['product_code']);

        $sofort->setSuccessUrl($fields['success_url'], $fields['success_link_redirect']);
        $sofort->setAbortUrl($fields['abort_url']);
        $sofort->setNotificationUrl($fields['notification_url'], $fields['notify_on']);

        $sofort->sendRequest();

        return array_filter([
            'error' => $sofort->getError(),
            'transaction_id' => $sofort->getTransactionId(),
            'payment_url' => $sofort->getPaymentUrl(),
        ]);
    }

    /**
     * @param $transactionId
     *
     * @return array
     *
    public function getTransactionData($transactionId)
    {
        $transactionData = new TransactionData($this->options['config_key']);
        $transactionData->addTransaction($transactionId);
        $transactionData->setApiVersion('2.0');
        $transactionData->sendRequest();

        $fields = array();
        $methods = array(
            'getAmount' => '',
            'getAmountRefunded' => '',
            'getCount' => '',
            'getPaymentMethod' => '',
            'getConsumerProtection' => '',
            'getStatus' => '',
            'getStatusReason' => '',
            'getStatusModifiedTime' => '',
            'getLanguageCode' => '',
            'getCurrency' => '',
            'getTransaction' => '',
            'getReason' => array(0,0),
            'getUserVariable' => 0,
            'getTime' => '',
            'getProjectId' => '',
            'getRecipientHolder' => '',
            'getRecipientAccountNumber' => '',
            'getRecipientBankCode' => '',
            'getRecipientCountryCode' => '',
            'getRecipientBankName' => '',
            'getRecipientBic' => '',
            'getRecipientIban' => '',
            'getSenderHolder' => '',
            'getSenderAccountNumber' => '',
            'getSenderBankCode' => '',
            'getSenderCountryCode' => '',
            'getSenderBankName' => '',
            'getSenderBic' => '',
            'getSenderIban' => '',
        );

        foreach ($methods as $method => $params) {
            $varName = $method;
            $varName = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1_$2', substr($varName, 3)));

            if (count($params) == 2) {
                $fields[$varName] = $transactionData->$method($params[0], $params[1]);
            } elseif ($params !== '') {
                $fields[$varName] = $transactionData->$method($params);
            } else {
                $fields[$varName] = $transactionData->$method();
            }
        }

        if ($transactionData->isError()) {
            $fields['error'] = $transactionData->getError();
        }

        return $fields;
    }

    /**
     * @param array $fields
     *
     * @return array
     *
    public function refundTransaction(array $fields)
    {
        $refund = new Refund($this->options['config_key']);
        $refund->setSenderSepaAccount($fields['recipient_bic'], $fields['recipient_iban'], $fields['recipient_holder']);
        $refund->addRefund($fields['transaction_id'], $fields['refund_amount']);
        $refund->setPartialRefundId(md5(uniqid()));
        $refund->setReason($fields['reason']);
        $refund->sendRequest();

        if ($refund->isError()) {
            $fields['refund_error'] = $refund->getError();
        } else {
            $fields['refund_url'] = $refund->getPaymentUrl();
        }

        return $fields;
    }
    */
}
