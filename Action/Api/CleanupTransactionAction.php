<?php

namespace Payum\PostfinanceDirectLink\Action\Api;

use Payum\PostfinanceDirectLink\Request\Api\CaptureTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\PostfinanceDirectLink\Request\Api\CleanupTransaction;

class CleanupTransactionAction extends BaseApiAwareAction
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
        $details->replace($this->api->cleanupTransaction((array) $details));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CleanupTransaction &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
