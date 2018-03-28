<?php
namespace Payum\Checkoutcom\Action\Api;

use Payum\Checkoutcom\Request\Api\ObtainSnippet;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\RenderTemplate;

class ObtainSnippetAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param ObtainToken $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($renderTemplate = new RenderTemplate('@PayumCheckoutcom/Action/checkout_snippet.html.twig', [
            'publishableKey' => $this->api->getOptions()['publishable_key'],
            'checkoutjsPath' => $this->api->getOptions()['checkoutjs_path'],
        ]));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ObtainSnippet &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
