<?php

namespace SkriptManufaktur\SimpleRestBundle\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class RequestListener
{
    public const string REQ_SERVER = '_requesting_server';


    public function __construct(private string $defaultRequestingServer)
    {
    }

    /**
     * When any request appears to this API, save the requesting server URL as a simple key in our request
     *
     * @param RequestEvent $event
     */
    public function onRequestProceed(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $requestingServer = $request->server->get('HTTP_ORIGIN', $this->defaultRequestingServer);
        $requestingServer = rtrim($requestingServer, '/');

        $request->attributes->set(self::REQ_SERVER, $requestingServer);
    }
}
