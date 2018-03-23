<?php
namespace Payum\Checkoutcom\Action;

use com\checkout\ApiClient;
use com\checkout\ApiServices\Cards\ResponseModels\Card;
use com\checkout\ApiServices\Charges\ChargeService;
use com\checkout\ApiServices\Charges\RequestModels\CardIdChargeCreate;
use com\checkout\ApiServices\Charges\RequestModels\CardTokenChargeCreate;
use com\checkout\helpers\ApiHttpClientCustomException;
use Payum\Checkoutcom\Action\Api\BaseApiAwareAction;
use Payum\Checkoutcom\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Authorize;
use Payum\Core\Exception\RequestNotSupportedException;

class AuthorizeAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Authorize $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        /** @var ArrayObject $model */
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty([
            'amount', 'currency', 'trackId', 'paymentToken', 'customerEmail'
        ]);

        /** @var ApiClient $checkoutApiClient */
        $checkoutApiClient = $this->api->getCheckoutApiClient();
        $chargeService = $checkoutApiClient->chargeService();

        switch ($model->offsetGet('paymentType')) {
            case Api::PAYMENT_TYPE_CARD_TOKEN:
                $result = $this->chargeCardToken($chargeService, $model);
                break;
            case Api::PAYMENT_TYPE_CARD_ID:
                $result = $this->chargeCardId($chargeService, $model);
                break;
            default:
                throw new LogicException(
                    sprintf('The paymentType %s is not supported.', $model['paymentType'])
                );
                break;
        }


        return $result;
    }

    /**
     * @param ChargeService $chargeService
     * @param ArrayObject $model
     * @return ArrayObject
     */
    protected function chargeCardId($chargeService, $model)
    {
        $charge = new CardIdChargeCreate();
        $charge->setEmail($model['customerEmail']);
        $charge->setAutoCapture($model['autoCapture'] === true ? 'Y' : 'N');
        $charge->setAutoCapTime($model['autoCaptureTime']);
        $charge->setValue($model['amount']);
        $charge->setCurrency($model['currency']);
        $charge->setTrackId($model['trackId']);
        $charge->setCardId($model['paymentToken']);
        $charge->setDescription($model['description']);

        try {
            $chargeResponse = $chargeService->chargeWithCardId($charge);

        } catch (ApiHttpClientCustomException $e) {
            throw new InvalidArgumentException($e->getErrorMessage(), $e->getCode());
        }

        $model['responseCode'] = $chargeResponse->getResponseCode();
        $model['status'] = $chargeResponse->getStatus();
        $model['live'] = $chargeResponse->getLiveMode();
        $model['chargeId'] = $chargeResponse->getId();
        $model['chargeMode'] = $chargeResponse->getChargeMode();
        $model['chargeCreated'] = $chargeResponse->getCreated();
        $model['responseMessage'] = $chargeResponse->getResponseMessage();

        /** @var Card $cardDetails */
        $cardDetails = $chargeResponse->getCard();

        $model['card'] = [
            'cardId' => $cardDetails->getId(),
            'cardLast4' => $cardDetails->getLast4(),
            'cardExpiryYear' => $cardDetails->getExpiryYear(),
            'cardExpiryMonth' => $cardDetails->getExpiryMonth(),
            'cardType' => $cardDetails->getPaymentMethod(),
        ];

        $model['customerId'] = $cardDetails->getCustomerId();

        $model['originalResponse'] = $chargeResponse->json;

        return $model;
    }

    /**
     * @param ChargeService $chargeService
     * @param ArrayObject $model
     * @return ArrayObject
     */
    protected function chargeCardToken($chargeService, $model)
    {
        $charge = new CardTokenChargeCreate();
        $charge->setEmail($model['customerEmail']);
        $charge->setAutoCapture($model['autoCapture'] === true ? 'Y' : 'N');
        $charge->setAutoCapTime($model['autoCaptureTime']);
        $charge->setValue($model['amount']);
        $charge->setCurrency($model['currency']);
        $charge->setTrackId($model['trackId']);
        $charge->setCardToken($model['paymentToken']);
        $charge->setDescription($model['description']);


        try {
            $chargeResponse = $chargeService->chargeWithCardToken($charge);

        } catch (ApiHttpClientCustomException $e) {
            throw new InvalidArgumentException($e->getErrorMessage(), $e->getCode());
        }

        $model['responseCode'] = $chargeResponse->getResponseCode();
        $model['status'] = $chargeResponse->getStatus();
        $model['live'] = $chargeResponse->getLiveMode();
        $model['chargeId'] = $chargeResponse->getId();
        $model['chargeMode'] = $chargeResponse->getChargeMode();
        $model['chargeCreated'] = $chargeResponse->getCreated();
        $model['responseMessage'] = $chargeResponse->getResponseMessage();

        /** @var Card $cardDetails */
        $cardDetails = $chargeResponse->getCard();

        $model['card'] = [
            'cardId' => $cardDetails->getId(),
            'cardLast4' => $cardDetails->getLast4(),
            'cardExpiryYear' => $cardDetails->getExpiryYear(),
            'cardExpiryMonth' => $cardDetails->getExpiryMonth(),
            'cardType' => $cardDetails->getPaymentMethod(),
        ];

        $model['customerId'] = $cardDetails->getCustomerId();

        $model['originalResponse'] = $chargeResponse->json;

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
