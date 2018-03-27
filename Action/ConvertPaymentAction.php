<?php

namespace Payum\Checkoutcom\Action;

use Payum\Checkoutcom\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{

    /**
     * @param mixed $request
     *
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $paymentDetails = ArrayObject::ensureArrayObject($payment->getDetails());

        if (null == $paymentDetails->offsetGet('paymentType')) {
            $paymentDetails->offsetSet('paymentType', Api::PAYMENT_TYPE_CARD_TOKEN);
        }

        $this->parseAutoCapture($paymentDetails);

        $paymentDetails->offsetSet('customerEmail', $payment->getClientEmail());
        $paymentDetails->offsetSet('amount', $payment->getTotalAmount());
        $paymentDetails->offsetSet('currency', $payment->getCurrencyCode());
        $paymentDetails->offsetSet('description', $payment->getDescription());


        $request->setResult($paymentDetails);
    }

    /**
     * @param ArrayObject $paymentDetails
     */
    public function parseAutoCapture($paymentDetails)
    {
        if (
            null === $paymentDetails->offsetGet('autoCapture') ||
            !is_bool($paymentDetails->offsetGet('autoCapture'))
        ) {
            $paymentDetails->offsetSet('autoCapture', true);
        }

        if (
            true === $paymentDetails->offsetGet('autoCapture')
            && null === $paymentDetails->offsetGet('autoCaptureTime')
        ) {
            $paymentDetails->offsetSet('autoCaptureTime', 0);
        }

        $autoCaptureTime = $paymentDetails->get('autoCaptureTime');
        if ($autoCaptureTime < 0 || $autoCaptureTime > 168) {
            throw new LogicException('autoCaptureTime time should be from 0 to 168');
        }
    }


    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array';
    }
}