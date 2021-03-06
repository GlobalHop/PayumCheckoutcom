<?php
namespace Payum\Checkoutcom\Action;

use com\checkout\ApiClient;
use com\checkout\ApiServices\Charges\ChargeService;
use com\checkout\ApiServices\Charges\RequestModels\ChargeCapture;
use com\checkout\helpers\ApiHttpClientCustomException;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Checkoutcom\Action\Api\BaseApiAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $model->validateNotEmpty(['amount', 'chargeId']);

        /** @var ApiClient $checkoutApiClient */
        $checkoutApiClient = $this->api->getCheckoutApiClient();
        /** @var ChargeService $chargeService */
        $chargeService = $checkoutApiClient->chargeService();

        $chargeCapturePayload = new ChargeCapture();
        $chargeCapturePayload->setChargeId($model['chargeId']);
        $chargeCapturePayload->setValue($model['amount']);

        try {
            $captureResponse = $chargeService->CaptureCardCharge($chargeCapturePayload);
        } catch (ApiHttpClientCustomException $e) {
            throw new InvalidArgumentException($e->getErrorMessage(), $e->getErrorCode(), $e);
        }

        $model['responseCode'] = $captureResponse->getResponseCode();
        $model['status'] = $captureResponse->getStatus();
        $model['chargeId'] = $captureResponse->getId();
        $model['responseMessage'] = $captureResponse->getResponseMessage();
        $model['json'] = $captureResponse->json;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
