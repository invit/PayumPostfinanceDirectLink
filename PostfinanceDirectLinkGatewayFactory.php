<?php
namespace Payum\PostfinanceDirectLink;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\PostfinanceDirectLink\Action\Api\CleanupTransactionAction;
use Payum\PostfinanceDirectLink\Action\Api\CreateTransactionAction;
use Payum\PostfinanceDirectLink\Action\Api\CaptureTransactionAction;
use Payum\PostfinanceDirectLink\Action\AuthorizeAction;
use Payum\PostfinanceDirectLink\Action\CaptureAction;
use Payum\PostfinanceDirectLink\Action\CleanupAction;
use Payum\PostfinanceDirectLink\Action\StatusAction;
use Payum\PostfinanceDirectLink\Request\Api\CleanupTransaction;

class PostfinanceDirectLinkGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults(array(
            'payum.factory_name' => 'postfinance_direct_link',
            'payum.factory_title' => 'Postfinance DirectLink',
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
            //'payum.action.refund' => new RefundAction(), TODO: refund

            'payum.action.api.create_transaction' => new CreateTransactionAction(),
            'payum.action.api.capture_transaction' => new CaptureTransactionAction(),
            'payum.action.api.cleanup_transaction' => new CleanupTransactionAction(),
            //'payum.action.api.refund_transaction' => new RefundTransactionAction(), TODO: refund
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'user' => 'DIRECTLINK',
                'environment' => Api::TEST
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['sha-in-passphrase', 'password', 'pspid'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $postfinanceConfig = [
                    'sha-in-passphrase' => $config['sha-in-passphrase'],
                    'user' => $config['user'],
                    'password' => $config['password'],
                    'pspid' => $config['pspid'],
                    'environment' => $config['environment'],
                ];

                return new Api($postfinanceConfig, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
