<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

if (PHP_VERSION_ID >= 80000) {
    /**
     * Class ApiResponse
     */
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

        private array $apiData;
        private array $apiMessages = self::EMPTY_MESSAGES;
        private array $validation = [];
        private Throwable|null $throwable = null;


        /**
         * ApiResponse constructor.
         *
         * @param array $data
         * @param int   $status
         * @param array $headers
         */
        public function __construct(array $data = [], int $status = 200, array $headers = [])
        {
            parent::__construct([], $status, $headers, false);
            $this->setData($data);

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
        }

        /**
         * Factory method for chainability
         *
         * @param array $data
         * @param int   $status
         * @param array $headers
         *
         * @return static
         */
        public static function create(array $data = [], int $status = 200, array $headers = []): static
        {
            return new static($data, $status, $headers);
        }

        /**
         * Assemble a json construction before preparing
         *
         * @param Request $request
         *
         * @return static
         */
        public function prepare(Request $request): static
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

            return parent::prepare($request);
        }

        public function getData(): array
        {
            return $this->apiData;
        }

        public function setData(mixed $data = []): static
        {
            if (!is_array($data)) {
                throw new Exception('ApiResponse::setData() can only be used with an array as its parameter!');
            }

            $this->apiData = $data;

            return $this;
        }

        public function getMessages(?string $type = null): array
        {
            if (null !== $type && array_key_exists($type, $this->apiMessages)) {
                return $this->apiMessages[$type];
            }

            return $this->apiMessages;
        }

        public function addMessage(string $type, string $message): static
        {
            if (array_key_exists($type, $this->apiMessages)) {
                $this->apiMessages[$type][] = $message;
            }

            return $this;
        }

        public function mergeMessages(string $type, array $messages): static
        {
            if (array_key_exists($type, $this->apiMessages)) {
                $this->apiMessages[$type] = array_merge($this->apiMessages[$type], $messages);
            }

            return $this;
        }

        public function clearMessages(?string $type = null): static
        {
            if (null !== $type && array_key_exists($type, $this->apiMessages)) {
                $this->apiMessages[$type] = [];
            } else {
                $this->apiMessages = self::EMPTY_MESSAGES;
            }

            return $this;
        }

        public function addValidationIssue(string $component, string $message): static
        {
            if ('' === $component) {
                $component = self::VALID_ROOT;
            }

            if (!array_key_exists($component, $this->validation)) {
                $this->validation[$component] = [];
            }

            $this->validation[$component][] = $message;

            return $this;
        }

        public function mergeValidationIssues(string $component, array $messages): static
        {
            if ('' === $component) {
                $component = self::VALID_ROOT;
            }

            if (!array_key_exists($component, $this->apiMessages)) {
                $this->validation[$component] = [];
            }

            $this->validation[$component] = array_merge($this->validation[$component], $messages);

            return $this;
        }

        public function clearValidation(?string $component = null): static
        {
            if (null !== $component && array_key_exists($component, $this->validation)) {
                unset($this->validation[$component]);
            } else {
                $this->validation = [];
            }

            return $this;
        }

        public function getThrowable(): ?Throwable
        {
            return $this->throwable;
        }

        public function setThrowable(?Throwable $throwable): static
        {
            $this->throwable = $throwable;

            return $this;
        }
    }
} else {
    /**
     * Class ApiResponse
     */
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

        private array $apiData;
        private array $apiMessages = self::EMPTY_MESSAGES;
        private array $validation = [];
        private ?Throwable $throwable = null;


        /**
         * ApiResponse constructor.
         *
         * @param array|null $data
         * @param int        $status
         * @param array      $headers
         */
        public function __construct(?array $data = null, int $status = 200, array $headers = [])
        {
            parent::__construct([], $status, $headers, false);
            $this->apiData = $data ?? [];

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
            return new static($data, $status, $headers);
        }

        /**
         * Assemble a json construction before preparing
         *
         * @param Request $request
         *
         * @return ApiResponse
         */
        public function prepare(Request $request): JsonResponse
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

            return parent::prepare($request);
        }

        public function getData(): array
        {
            return $this->apiData;
        }

        public function setData($data = []): self
        {
            $this->apiData = $data;

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
            }

            return $this;
        }

        public function mergeMessages(string $type, array $messages): self
        {
            if (array_key_exists($type, $this->apiMessages)) {
                $this->apiMessages[$type] = array_merge($this->apiMessages[$type], $messages);
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

            return $this;
        }

        public function clearValidation(?string $component = null): self
        {
            if (null !== $component && array_key_exists($component, $this->validation)) {
                unset($this->validation[$component]);
            } else {
                $this->validation = [];
            }

            return $this;
        }

        public function getThrowable(): ?Throwable
        {
            return $this->throwable;
        }

        public function setThrowable(?Throwable $throwable): self
        {
            $this->throwable = $throwable;

            return $this;
        }
    }
}
