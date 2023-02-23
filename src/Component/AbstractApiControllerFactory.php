<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use SkriptManufaktur\SimpleRestBundle\Pagination\Pagination;
use Symfony\Component\HttpFoundation\Request;
use function array_map;
use function in_array;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function sprintf;
use function strtoupper;

abstract class AbstractApiControllerFactory extends AbstractApiHandlerFactory
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws ApiProcessException
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
