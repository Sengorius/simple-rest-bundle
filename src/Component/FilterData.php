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
    private array $search = [];
    private array $sort = [];
    private bool $booted = false;


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

    public static function createFromAttributes(string $filterInterface, array $search = [], array $sort = []): self
    {
        return (new self())
            ->satisfyApiFilterInterface($filterInterface)
            ->mergeAttributes($search)
            ->mergeSortings($sort)
        ;
    }

    public static function createFromRequest(string $filterInterface, Request $request): self
    {
        return (new self())
            ->satisfyApiFilterInterface($filterInterface)
            ->mergeRequest($request)
        ;
    }

    public function satisfyApiFilterInterface(string $filterInterface): self
    {
        // consolidate the class of being filterable
        try {
            $ref = new ReflectionClass($filterInterface);
        } catch (ReflectionException) {
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

        $this->search[$key] = $value;

        // add possible type casting for booleans
        if (null !== $value && !is_array($value) && in_array(strtolower($value), ['true', 'false', 't', 'f'])) {
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

    public function mergeAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->addAttribute($key, $value);
        }

        return $this;
    }

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
        if ($this->booted) {
            return $this;
        }

        $searchResolver = clone $this->searchResolver;
        $sortResolver = clone $this->sortResolver;

        $this->search = $searchResolver->resolve($this->search);
        $this->sort = $sortResolver->resolve($this->sort);
        $this->booted = true;

        return $this;
    }

    public function __serialize(): array
    {
        $this->boot();

        return [
            'search' => $this->getSearch(),
            'sort' => $this->getSorting(),
            'interface' => $this->filterInterface,
        ];
    }

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

    public function serialize(): string|null
    {
        return json_encode($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(json_decode($data, true));
    }

    public function getSearch(string|null $key = null): array|string|int|bool|null
    {
        if (null !== $key) {
            if (!$this->searchResolver->hasDefault($key)) {
                throw new ApiProcessException(sprintf('FilterData: Option "%s" is not configured!', $key));
            }

            if (!array_key_exists($key, $this->search)) {
                throw new ApiProcessException('FilterData was not booted!');
            }

            return $this->search[$key];
        }

        return $this->search;
    }

    public function getSorting(string|null $key = null): array|string|null
    {
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
        if (!isset($this->search['page'])) {
            throw new ApiProcessException('FilterData was not booted!');
        }

        return $this->search['page'] ?: 0;
    }
}
