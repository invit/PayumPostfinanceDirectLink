<?php

namespace Payum\PostfinanceDirectLink\Action\Api;

use Payum\PostfinanceDirectLink\Request\Api\CaptureTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureTransactionAction extends BaseApiAwareAction
{
    /**
     * {@inheritdoc}
     *
     * @param $request CaptureTransaction
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $details->validateNotEmpty(['PAYID']);

        $details->replace($this->api->captureTransaction((array) $details));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CaptureTransaction &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
