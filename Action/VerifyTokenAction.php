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
use Payum\Checkoutcom\Request\Api\VerifyToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Authorize;

class VerifyTokenAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface
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

        $model->validateNotEmpty(
            [
                'chargeId',
            ]
        );

        /** @var ApiClient $checkoutApiClient */
        $checkoutApiClient = $this->api->getCheckoutApiClient();
        $chargeService = $checkoutApiClient->chargeService();

        try {
            $verifyTokenResponse = $chargeService->verifyCharge($model['chargeId']);
        } catch (ApiHttpClientCustomException $e) {
            throw new InvalidArgumentException($e->getErrorMessage(), $e->getErrorCode(), $e);
        }

        return $this->buildResponse($model, $verifyTokenResponse);
    }

    /**
     * @param ArrayObject                                             $model
     * @param \com\checkout\ApiServices\Charges\ResponseModels\Charge $chargeResponse
     *
     * @return ArrayObject
     */
    protected function buildResponse($model, $chargeResponse)
    {
        $model['responseType'] = $chargeResponse->getResponseType();
        $model['responseCode'] = $chargeResponse->getResponseCode();
        $model['status'] = $chargeResponse->getStatus();
        $model['live'] = $chargeResponse->getLiveMode();
        $model['chargeId'] = $chargeResponse->getId();
        $model['chargeMode'] = $chargeResponse->getChargeMode();
        $model['chargeCreated'] = $chargeResponse->getCreated();
        $model['responseMessage'] = $chargeResponse->getResponseMessage();
        $model['redirectUrl'] = $chargeResponse->getRedirectUrl();
        $model['successUrl'] = $chargeResponse->getFailUrl();
        $model['failUrl'] = $chargeResponse->getSuccessUrl();


        /** @var Card $cardDetails */
        $cardDetails = $chargeResponse->getCard();

        if ($cardDetails) {
            $model['card'] = [
                'cardId' => $cardDetails->getId(),
                'cardLast4' => $cardDetails->getLast4(),
                'cardFingerprint' => $cardDetails->getFingerprint(),
                'cardExpiryYear' => $cardDetails->getExpiryYear(),
                'cardExpiryMonth' => $cardDetails->getExpiryMonth(),
                'cardType' => $cardDetails->getPaymentMethod(),
            ];
        }

        $model['originalResponse'] = $chargeResponse->json;


        return $model;
    }

    /**
     * @param $model
     *
     * @return array
     */
    protected function convertProducts($model)
    {
        $products = [];
        if (!isset($model['products']) || empty($model['products'])) {
            return $products;
        }

        foreach ($model['products'] as $p) {
            $product = new \com\checkout\ApiServices\SharedModels\Product();
            if (!empty($p['name'])) {
                $product->setName($p['name']);
            }
            if (!empty($p['price']) && is_numeric($p['price'])) {
                $product->setPrice($p['price']);
            }
            if (!empty($p['sku'])) {
                $product->setSku($p['sku']);
            }
            if (!empty($p['description'])) {
                $product->setDescription($p['description']);
            }
            if (!empty($p['quantity'])) {
                $product->setQuantity($p['quantity']);
            }
            if (!empty($p['image'])) {
                $product->setQuantity($p['image']);
            }
            $products[] = $product;
        }

        return $products;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof VerifyToken &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
