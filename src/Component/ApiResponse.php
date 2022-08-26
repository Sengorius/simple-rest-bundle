<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class ApiResponse extends JsonResponse
{
    const MSGTYPE_SUCCESS = 'success';
    const MSGTYPE_INFO = 'info';
    const MSGTYPE_WARNING = 'warning';
    const MSGTYPE_ERROR = 'error';
    const EMPTY_MESSAGES = [
        self::MSGTYPE_SUCCESS => [],
        self::MSGTYPE_INFO => [],
        self::MSGTYPE_WARNING => [],
        self::MSGTYPE_ERROR => [],
    ];
    const VALID_ROOT = 'root';

    private array $apiData = [];
    private array $apiMessages = self::EMPTY_MESSAGES;
    private array $validation = [];
    private ?Throwable $throwable = null;


    public function __construct(?array $data = null, int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers, true);

        // add security headers; see https://observatory.mozilla.org/
        $this->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $this->headers->set('Vary', 'Accept');
        $this->headers->set('X-Content-Type-Options', 'nosniff');
        $this->headers->set('X-Frame-Options', 'deny');

        // add cache headers
        $this->headers->addCacheControlDirective('no-cache');
        $this->headers->addCacheControlDirective('no-store');
        $this->headers->addCacheControlDirective('must-revalidate');
        $this->headers->set('Expires', '0');
        $this->headers->set('Pragma', 'no-cache');

        $this->setData($data ?? []);
    }

    /**
     * Factory method for chainability
     *
     * @param array|null $data
     * @param int        $status
     * @param array      $headers
     *
     * @return ApiResponse
     */
    public static function create($data = null, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }

    public function getData(): array
    {
        return $this->apiData;
    }

    public function setData($data = []): self
    {
        $this->apiData = $data;
        $this->updateApiData();

        return $this;
    }

    public function getMessages(?string $type = null): array
    {
        if (null !== $type && array_key_exists($type, $this->apiMessages)) {
            return $this->apiMessages[$type];
        }

        return $this->apiMessages;
    }

    public function addMessage(string $type, string $message): self
    {
        if (array_key_exists($type, $this->apiMessages)) {
            $this->apiMessages[$type][] = $message;
            $this->updateApiData();
        }

        return $this;
    }

    public function mergeMessages(string $type, array $messages): self
    {
        if (array_key_exists($type, $this->apiMessages)) {
            $this->apiMessages[$type] = array_merge($this->apiMessages[$type], $messages);
            $this->updateApiData();
        }

        return $this;
    }

    public function clearMessages(?string $type = null): self
    {
        if (null !== $type && array_key_exists($type, $this->apiMessages)) {
            $this->apiMessages[$type] = [];
        } else {
            $this->apiMessages = self::EMPTY_MESSAGES;
        }

        $this->updateApiData();

        return $this;
    }

    public function addValidationIssue(string $component, string $message): self
    {
        if ('' === $component) {
            $component = self::VALID_ROOT;
        }

        if (!array_key_exists($component, $this->validation)) {
            $this->validation[$component] = [];
        }

        $this->validation[$component][] = $message;
        $this->updateApiData();

        return $this;
    }

    public function mergeValidationIssues(string $component, array $messages): self
    {
        if ('' === $component) {
            $component = self::VALID_ROOT;
        }

        if (!array_key_exists($component, $this->apiMessages)) {
            $this->validation[$component] = [];
        }

        $this->validation[$component] = array_merge($this->validation[$component], $messages);
        $this->updateApiData();

        return $this;
    }

    public function clearValidation(?string $component = null): self
    {
        if (null !== $component && array_key_exists($component, $this->validation)) {
            unset($this->validation[$component]);
        } else {
            $this->validation = [];
        }

        $this->updateApiData();

        return $this;
    }

    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }

    public function setThrowable(?Throwable $throwable): self
    {
        $this->throwable = $throwable;
        $this->updateApiData();

        return $this;
    }

    protected function updateApiData(): self
    {
        if (null !== $this->throwable) {
            $this->apiData = [
                'message' => $this->throwable->getMessage(),
                'status' => method_exists($this->throwable, 'getStatusCode') ? $this->throwable->getStatusCode() : 0,
                'code' => $this->throwable->getCode(),
            ];
        }

        parent::setData([
            'data' => $this->apiData,
            'messages' => $this->apiMessages,
            'validation' => $this->validation,
        ]);

        return $this;
    }
}
