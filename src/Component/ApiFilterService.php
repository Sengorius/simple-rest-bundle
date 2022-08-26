<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use ReflectionClass;
use ReflectionException;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiFilterService
{
    /**
     * Create an OptionResolver for search and sorting parameters and match the default
     * values from a given object implementing FilterInterface
     *
     * @param string $filterInterface
     *
     * @return OptionsResolver[]
     *
     * @throws ReflectionException
     */
    public function createOptionResolvers(string $filterInterface): array
    {
        // consolidate the class of being filterable
        $ref = new ReflectionClass($filterInterface);

        if (!$ref->implementsInterface(ApiFilterInterface::class)) {
            throw new ApiProcessException(sprintf(
                'Class "%s" does not implement interface %s and can therefore not be used as a filter!',
                $filterInterface,
                ApiFilterInterface::class
            ));
        }

        // create a new resolver and set default values for any filter
        $sorts = new OptionsResolver();
        $searches = new OptionsResolver();
        $searches
            ->setDefault('page', 0)
            ->setRequired('page')
            ->setAllowedTypes('page', ['int', 'string'])
        ;

        // add the option and sorting defaults from given filter interface
        $filterInterface = $ref->newInstanceWithoutConstructor();
        $filterInterface->setFilterAttributes($searches);
        $filterInterface->setOrderAttributes($sorts);

        return [$searches, $sorts];
    }

    /**
     * Takes both option resolvers created by createOptionResolvers() and resolves
     * the given attributes, then returns a structure for repositories to take
     *
     * @param OptionsResolver $searchResolver
     * @param OptionsResolver $sortResolver
     * @param array           $searches
     * @param array           $sorts
     *
     * @return array[]
     */
    public function resolveFilterData(OptionsResolver $searchResolver, OptionsResolver $sortResolver, array $searches = [], array $sorts = []): array
    {
        return [
            'search' => $searchResolver->resolve($searches),
            'sort' => $sortResolver->resolve($sorts),
        ];
    }
}
