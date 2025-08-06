<?php

namespace SkriptManufaktur\SimpleRestBundle\Pagination;

/** @template-covariant T of mixed */
abstract class AbstractPagination
{
    /**
     * Return items for a given page
     *
     * @param int|null $page
     *
     * @return array<T>
     */
    abstract public function getPage(int|null $page = null): array;

    /**
     * Return items of the next page by currentPage + 1
     *
     * @return array<T>
     */
    abstract public function nextPage(): array;

    /**
     * Return items of the previous page by currentPage - 1
     *
     * @return array<T>
     */
    abstract public function prevPage(): array;

    /**
     * Update current page
     *
     * @return AbstractPagination<T>
     */
    abstract public function next(): AbstractPagination;

    /**
     * Update current page
     *
     * @return AbstractPagination<T>
     */
    abstract public function prev(): AbstractPagination;

    /**
     * @return array<T>
     */
    abstract public function getItems(): array;

    abstract public function getItemCount(): int;

    abstract public function getItemsPerPage(): int;

    abstract public function getMaxPages(): int;

    abstract public function getItemClass(): string|null;

    abstract public function getCurrentPage(): int;

    /**
     * @param int $currentPage
     *
     * @return AbstractPagination<T>
     */
    abstract public function setCurrentPage(int $currentPage): AbstractPagination;

    abstract public function getLowerBound(): int|null;

    abstract public function getUpperBound(): int|null;

    abstract public function isSatisfied(): bool;

    abstract public function isFirstPage(): bool;

    abstract public function isLastPage(): bool;

    abstract public function isEmpty(): bool;

    abstract public function hasFilters(): bool;

    /**
     * @return callable[]
     */
    abstract public function getFilters(): array;

    /**
     * @param callable $filter
     *
     * @return AbstractPagination<T>
     */
    abstract public function addFilter(callable $filter): AbstractPagination;

    /**
     * @param int $key
     *
     * @return AbstractPagination<T>
     */
    abstract public function removeFilter(int $key): AbstractPagination;
}
