<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Exception;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use SkriptManufaktur\SimpleRestBundle\Pagination\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;

/**
 * Class ApiControllerFactory
 */
abstract class AbstractApiControllerFactory extends AbstractApiHandlerFactory
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws Exception
     */
    protected function unpackRequestContent(Request $request): array
    {
        $content = $request->getContent();
        $method = strtoupper($request->getMethod());

        if (in_array($method, ['POST', 'PUT', 'PATCH']) && empty($content)) {
            throw new ApiProcessException(sprintf('HTTP method is %s, but the request is empty!', $method));
        }

        $content = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ApiProcessException(sprintf('Error while unpacking JSON: "%s"', json_last_error_msg()));
        }

        return $content;
    }

    /**
     * Collect and test parameters from query string into an array
     * It is used like so: https://domain.tld/path?email=mail@example.org&created[lte]=2020-01-01&sort[created]=desc
     *
     * @param Request $request
     * @param string  $filterInterface
     *
     * @return array
     *
     * @throws Exception
     */
    protected function collectFilterData(Request $request, string $filterInterface): array
    {
        [$searches, $sorts] = $this->apiFilter->createOptionResolvers($filterInterface);

        // collect any query parameter that is known by current filter defaults
        $queryParameters = [];
        $sortParameters = [];
        foreach ($request->query->all() as $key => $value) {
            // normalize the page
            if ('page' === $key) {
                $page = (int) $value;

                if ($page >= 1) {
                    $queryParameters['page'] = $page - 1;
                }
            } elseif ('sort' === $key && is_array($value)) {
                foreach ($value as $sortField => $direction) {
                    $direction = is_string($direction) ? strtoupper($direction) : null;

                    if ($sorts->hasDefault($sortField) && in_array($direction, ['ASC', 'DESC'])) {
                        $sortParameters[$sortField] = $direction;
                    }
                }
            } elseif ($searches->hasDefault($key)) {
                $queryParameters[$key] = $value;

                // add possible type casting for booleans
                if (!is_array($value) && in_array(strtolower($value), ['true', 'false', 't', 'f'])) {
                    $searches->addNormalizer($key, fn (Options $options, $value) => in_array(strtolower($value), ['true', 't']));
                }
            }
        }

        return $this->apiFilter->resolveFilterData($searches, $sorts, $queryParameters, $sortParameters);
    }

    /**
     * Mapper for normalization of a collection of entities
     *
     * @param object[] $entities
     * @param string[] $groups
     *
     * @return array
     *
     * @throws ApiProcessException
     */
    protected function normalizeCollection(array $entities, array $groups = []): array
    {
        return array_map(fn (object $entity) => $this->normalize($entity, $groups), $entities);
    }

    /**
     * Maps the normalized collection from pagination + pagination data into an array
     *
     * @param Pagination $pagination
     * @param array      $groups
     *
     * @return array
     */
    protected function normalizePagination(Pagination $pagination, array $groups = []): array
    {
        $pagination->boot();

        return [
            'count' => $pagination->getItemCount(),
            'perPage' => $pagination->getItemsPerPage(),
            'maxPages' => $pagination->getMaxPages(),
            'page' => $pagination->getCurrentPage() + 1, // make it easier for the frontend
            'lowerBound' => $pagination->getLowerBound(),
            'upperBound' => $pagination->getUpperBound(),
            'isSatisfied' => $pagination->isSatisfied(),
            'isEmpty' => $pagination->isEmpty(),
            'isFirstPage' => $pagination->isFirstPage(),
            'isLastPage' => $pagination->isLastPage(),
            'items' => $this->normalizeCollection($pagination->getPage(), $groups),
        ];
    }
}
