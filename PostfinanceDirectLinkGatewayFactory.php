<?php
namespace Payum\PostfinanceDirectLink;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayFactory;
use Payum\PostfinanceDirectLink\Action\Api\CreateTransactionAction;
use Payum\PostfinanceDirectLink\Action\Api\CaptureTransactionAction;
use Payum\PostfinanceDirectLink\Action\AuthorizeAction;
use Payum\Sofort\Action\Api\GetTransactionDataAction;
use Payum\Sofort\Action\Api\RefundTransactionAction;
use Payum\PostfinanceDirectLink\Action\CaptureAction;
use Payum\Sofort\Action\ConvertPaymentAction;
use Payum\Sofort\Action\NotifyAction;
use Payum\Sofort\Action\RefundAction;
use Payum\PostfinanceDirectLink\Action\StatusAction;
use Payum\Sofort\Action\SyncAction;
use Sofort\SofortLib\Sofortueberweisung;

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
            //'payum.action.notify' => new NotifyAction(),
            //'payum.action.sync' => new SyncAction(),
            //'payum.action.refund' => new RefundAction(),
            //'payum.action.convert_payment' => new ConvertPaymentAction(),

            'payum.action.api.create_transaction' => new CreateTransactionAction(),
            'payum.action.api.capture_transation' => new CaptureTransactionAction(),
            //'payum.action.api.refund_transaction' => new RefundTransactionAction(),
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
                    'environment' => $config['pspid'],
                ];

                return new Api($postfinanceConfig, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
