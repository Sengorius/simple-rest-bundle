<?php

namespace SkriptManufaktur\SimpleRestBundle\Listener;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use SkriptManufaktur\SimpleRestBundle\Component\ApiResponse;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiBusException;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiNotFoundException;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use SkriptManufaktur\SimpleRestBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

final readonly class ApiResponseListener
{
    /** @param string[] $firewallNames */
    public function __construct(private array $firewallNames)
    {
    }

    /**
     * The API routes have to return an ApiResponse
     * Throws exception otherwise
     *
     * @param ResponseEvent $event
     *
     * @throws LogicException
     */
    public function testApiResponseType(ResponseEvent $event): void
    {
        if (!$this->belongsToFirewallContext($event->getRequest())) {
            return;
        }

        if (($response = $event->getResponse()) instanceof ApiResponse) {
            return;
        }

        throw new LogicException(sprintf('Response has to be an instance of ApiResponse, "%s" given!', get_class($response)));
    }

    /**
     * Merge the messages from Flashbag into the ApiResponse
     *
     * @param ResponseEvent $event
     */
    public function addFlashbagMessages(ResponseEvent $event): void
    {
        if (!$this->belongsToFirewallContext($event->getRequest())) {
            return;
        }

        $response = $event->getResponse();

        if (!$response instanceof ApiResponse) {
            return;
        }

        try {
            /** @var Session $session */
            $session = $event->getRequest()->getSession();

            foreach ($session->getFlashBag()->all() as $type => $messages) {
                $response->mergeMessages($type, $messages);
            }
        } catch (SessionNotFoundException) {
            // just catch and throw away
        }
    }

    /**
     * If some kind of exception is thrown in the process, catch it and put it into an ApiResponse
     *
     * @param ExceptionEvent $event
     */
    public function formatException(ExceptionEvent $event): void
    {
        if (!$this->belongsToFirewallContext($event->getRequest())) {
            return;
        }

        $response = $event->getResponse();

        if (!$response instanceof ApiResponse) {
            $response = ApiResponse::create();
        }

        // set default response information
        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $response->setThrowable($event->getThrowable());

        $this->handleExceptionInResponse($response);
        $event->setResponse($response);
    }

    /**
     * Try to handle any exception by type
     *
     * @param ApiResponse $response
     */
    private function handleExceptionInResponse(ApiResponse $response): void
    {
        $exception = $response->getThrowable();

        switch (true) {
            case $exception instanceof HandlerFailedException:
                $response->setThrowable($exception->getPrevious());
                $this->handleExceptionInResponse($response);
                break;

            case $exception instanceof AccessDeniedException:
            case $exception instanceof AccessDeniedHttpException:
                $response->setStatusCode(Response::HTTP_FORBIDDEN);
                break;

            case $exception instanceof ValidationException:
                $response->setThrowable(null);
                $response->setData(); // clears earlier set throwable from data output
                $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                $this->fetchValidationException($response, $exception);
                break;

            case $exception instanceof NotFoundHttpException:
            case $exception instanceof ApiNotFoundException:
            case $exception instanceof NonUniqueResultException:
                $response->setStatusCode(Response::HTTP_NOT_FOUND);
                break;

            case $exception instanceof ApiProcessException:
            case $exception instanceof ApiBusException:
                // we keep the default configuration
                break;

            default:
                // any other case is not explicitly handled, yet
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                break;
        }
    }

    /**
     * Copies validation messages from a ValidationException into a given ApiResponse
     *
     * @param ApiResponse         $response
     * @param ValidationException $exception
     */
    private function fetchValidationException(ApiResponse $response, ValidationException $exception): void
    {
        /** @var ConstraintViolationInterface $violation */
        foreach ($exception->getViolations() as $violation) {
            $response->addValidationIssue($violation->getPropertyPath(), $violation->getMessage());
        }
    }

    /**
     * Matches the given firewall names with the requested one
     *
     * @param Request $request
     *
     * @return bool
     */
    private function belongsToFirewallContext(Request $request): bool
    {
        $firewall = $request->attributes->get('_firewall_context');

        if (empty($firewall)) {
            return false;
        }

        foreach ($this->firewallNames as $fn) {
            if (1 === preg_match('/'.$fn.'$/', $firewall)) {
                return true;
            }
        }

        return false;
    }
}
