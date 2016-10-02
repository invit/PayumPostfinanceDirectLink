<?php

namespace Payum\PostfinanceDirectLink\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\PostfinanceDirectLink\Request\Api\CaptureTransaction;
use Payum\PostfinanceDirectLink\Request\Api\CreateTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use PostFinance\DirectLink\DirectLinkPaymentRequest;

class CaptureAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;
    
    /**
     * {@inheritdoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        /* @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $payment = $request->getModel();

        $status = new GetHumanStatus($payment);
        $this->gateway->execute($status, false, true);

        if ($status->isNew()) {
            $details['operation'] = DirectLinkPaymentRequest::OPERATION_REQUEST_DIRECT_SALE;
            $this->gateway->execute(new CreateTransaction($details));
        } elseif ($status->isAuthorized()) {
            $this->gateway->execute(new CaptureTransaction($details));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
