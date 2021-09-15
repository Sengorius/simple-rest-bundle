<?php

namespace SkriptManufaktur\SimpleRestBundle\Listener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class RequestListener
 */
class RequestListener
{
    const REQ_SERVER = '_requesting_server';

    private ParameterBagInterface $bag;
    private RequestStack $requestStack;


    /**
     * RequestListener constructor.
     *
     * @param ParameterBagInterface $bag
     * @param RequestStack          $requestStack
     */
    public function __construct(ParameterBagInterface $bag, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->bag = $bag;
    }

    /**
     * When any request appears to this API, save the requesting server URL as a simple key in our request
     *
     * @param RequestEvent $event
     */
    public function onRequestProceed(RequestEvent $event): void
    {
        $requestingServer = $event->getRequest()->server->get('HTTP_ORIGIN', $this->bag->get('app.domain_url'));
        $requestingServer = rtrim($requestingServer, '/');

        if (null !== ($currentRequest = $this->requestStack->getCurrentRequest())) {
            $currentRequest->attributes->set(self::REQ_SERVER, $requestingServer);
        }
    }
}
