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
}
