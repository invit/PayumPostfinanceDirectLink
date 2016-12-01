<?php

namespace Payum\PostfinanceDirectLink\Action;

use Payum\PostfinanceDirectLink\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use PostFinance\DirectLink\DirectLinkPaymentResponse;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     *
     * @param $request GetStatusInterface
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($details['STATUS']) || !strlen($details['STATUS'])) {
            $request->markNew();

            return;
        }

        switch ($details['STATUS']) {
            case DirectLinkPaymentResponse::STATUS_AUTHORISED:
                $request->markAuthorized();
                break;
            case DirectLinkPaymentResponse::STATUS_PAYMENT_REQUESTED:
            case DirectLinkPaymentResponse::STATUS_PAYMENT:
            # change to const as soon as PR is merged and STATUS_AUTHORISATION_CANCELLATION_WAITING is available
            case 61:
                $request->markCaptured();
                break;
            case DirectLinkPaymentResponse::STATUS_INCOMPLETE_OR_INVALID:
            case DirectLinkPaymentResponse::STATUS_AUTHORISATION_REFUSED:
            case DirectLinkPaymentResponse::STATUS_PAYMENT_REFUSED:
                $request->markFailed();
                break;
            case DirectLinkPaymentResponse::STATUS_REFUND:
                $request->markRefunded();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
