<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use ReflectionClass;
use ReflectionException;
use Serializable;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function strtolower;
use function strtoupper;

final class FilterData implements Serializable
{
    private OptionsResolver $searchResolver;
    private OptionsResolver $sortResolver;
    private string|null $filterInterface = null;
    private bool $booted = false;

    /** @var array<string, int|bool|string|string[]|null> */
    private array $search = [];

    /** @var array<string, string|null> */
    private array $sort = [];


    public function __construct()
    {
        // create a new resolver and set default values for any filter
        $this->sortResolver = new OptionsResolver();
        $this->searchResolver = new OptionsResolver();
        $this->searchResolver
            ->setDefault('page', 0)
            ->setRequired('page')
            ->setAllowedTypes('page', ['int', 'string'])
        ;
    }

    /**
     * @param class-string                                 $filterInterface
     * @param array<string, int|bool|string|string[]|null> $search
     * @param array<string, string|null>                   $sort
     *
     * @return self
     */
    public static function createFromAttributes(string $filterInterface, array $search = [], array $sort = []): self
    {
        return new self()
            ->satisfyApiFilterInterface($filterInterface)
            ->mergeAttributes($search)
            ->mergeSortings($sort)
        ;
    }

    /**
     * @param class-string $filterInterface
     * @param Request      $request
     *
     * @return self
     */
    public static function createFromRequest(string $filterInterface, Request $request): self
    {
        return new self()
            ->satisfyApiFilterInterface($filterInterface)
            ->mergeRequest($request)
        ;
    }

    /**
     * @param class-string $filterInterface
     *
     * @return $this
     */
    public function satisfyApiFilterInterface(string $filterInterface): self
    {
        // consolidate the class of being filterable
        try {
            $ref = new ReflectionClass($filterInterface);
        } catch (ReflectionException) { // @phpstan-ignore-line
            throw new ApiProcessException(sprintf(
                '"%s" is not a valid FQCN (class string)!',
                $filterInterface
            ));
        }

        if (!$ref->implementsInterface(ApiFilterInterface::class)) {
            throw new ApiProcessException(sprintf(
                'Class "%s" does not implement interface %s and can therefore not be used as a filter!',
                $filterInterface,
                ApiFilterInterface::class
            ));
        }

        try {
            /** @var ApiFilterInterface $interface */
            $interface = $ref->newInstanceWithoutConstructor();
            $interface->setFilterAttributes($this->searchResolver);
            $interface->setOrderAttributes($this->sortResolver);
        } catch (ReflectionException) {
            throw new ApiProcessException(sprintf(
                'The "%s" class is declared internal and final and can therefore not be instatiated without constructor!',
                $filterInterface
            ));
        }

        $this->filterInterface = $filterInterface;

        return $this;
    }

    /**
     * @param string                                        $key
     * @param string|int|bool|(string|int|bool|null)[]|null $value
     *
     * @return $this
     */
    public function addAttribute(string $key, string|int|bool|array|null $value): self
    {
        if ('page' === $key) {
            $page = (int) $value;

            if ($page >= 1) {
                $this->search['page'] = $page - 1;
            }

            return $this;
        }

        // simply add the value, if existing
        if (!$this->searchResolver->hasDefault($key)) {
            return $this;
        }

        $this->search[$key] = $value; // @phpstan-ignore-line

        // add possible type casting for booleans
        if (is_string($value) && in_array(strtolower($value), ['true', 'false', 't', 'f'])) {
            $this->searchResolver->addNormalizer(
                $key,
                fn (Options $options, $value): bool => in_array(strtolower($value), ['true', 't'])
            );
        }

        return $this;
    }

    public function addSorting(string $key, string|null $direction): self
    {
        $direction = is_string($direction) ? strtoupper($direction) : null;

        if ($this->sortResolver->hasDefault($key) && in_array($direction, ['ASC', 'DESC', null])) {
            $this->sort[$key] = $direction;
        }

        return $this;
    }

    /**
     * @param array<string, int|bool|string|string[]|null> $attributes
     *
     * @return $this
     */
    public function mergeAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->addAttribute($key, $value);
        }

        return $this;
    }

    /**
     * @param array<string, string|null> $sortings
     *
     * @return $this
     */
    public function mergeSortings(array $sortings): self
    {
        foreach ($sortings as $key => $direction) {
            $this->addSorting($key, $direction);
        }

        return $this;
    }

    /**
     * Collect and test parameters from query string into an array
     * It is used like so: https://domain.tld/path?email=mail@example.org&created[lte]=2020-01-01&sort[created]=desc
     *
     * @param Request $request
     *
     * @return $this
     */
    public function mergeRequest(Request $request): self
    {
        foreach ($request->query->all() as $key => $value) {
            if ('sort' === $key && is_array($value)) {
                $this->mergeSortings($value);
                continue;
            }

            $this->addAttribute($key, $value);
        }

        return $this;
    }

    public function boot(): self
    {
        $searchResolver = clone $this->searchResolver;
        $sortResolver = clone $this->sortResolver;

        $this->search = $searchResolver->resolve($this->search);
        $this->sort = $sortResolver->resolve($this->sort);
        $this->booted = true;

        return $this;
    }

    public function __serialize(): array
    {
        if (!$this->booted) {
            $this->boot();
        }

        return [
            'search' => $this->getSearch(),
            'sort' => $this->getSorting(),
            'interface' => $this->filterInterface,
        ];
    }

    /** @param array{ 'search'?: array<string, int|bool|string|string[]|null>, 'sort'?: array<string, string|null>, 'interface'?: class-string|null } $data */
    public function __unserialize(array $data): void
    {
        // fire the constructor, to init the option resolvers
        self::__construct();

        $this->search = $data['search'] ?? [];
        $this->sort = $data['sort'] ?? [];
        $this->filterInterface = $data['interface'] ?? null;
        $this->booted = false;

        if (!empty($this->filterInterface)) {
            $this->satisfyApiFilterInterface($this->filterInterface);
        }
    }

    public function serialize(): string|false
    {
        return json_encode($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(json_decode($data, true));
    }

    /**
     * @param string|null $key
     *
     * @return array<string, int|bool|string|string[]|null>|string|string[]|int|bool|null
     */
    public function getSearch(string|null $key = null): array|string|int|bool|null
    {
        if (!$this->booted) {
            $this->boot();
        }

        if (null !== $key) {
            if (!$this->searchResolver->hasDefault($key)) {
                throw new ApiProcessException(sprintf('FilterData: Option "%s" is not configured!', $key));
            }

            if (!array_key_exists($key, $this->search)) {
                throw new ApiProcessException('FilterData was not booted!');
            }

            return $this->search[$key];
        }

        return $this->search; // @phpstan-ignore-line
    }

    /**
     * @param string|null $key
     *
     * @return array<string, string|null>|string|null
     */
    public function getSorting(string|null $key = null): array|string|null
    {
        if (!$this->booted) {
            $this->boot();
        }

        if (null !== $key) {
            if (!$this->sortResolver->hasDefault($key)) {
                throw new ApiProcessException(sprintf('FilterData: Sorting "%s" is not configured!', $key));
            }

            if (!array_key_exists($key, $this->sort)) {
                throw new ApiProcessException('FilterData was not booted!');
            }

            return $this->sort[$key];
        }

        return $this->sort;
    }

    public function getPage(): int
    {
        if (!$this->booted) {
            $this->boot();
        }

        if (!isset($this->search['page'])) {
            throw new ApiProcessException('FilterData was not booted!');
        }

        return (int) $this->search['page'] ?: 0;
    }
}
