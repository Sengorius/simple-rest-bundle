<?php

namespace SkriptManufaktur\SimpleRestBundle\Pagination;

abstract class AbstractPagination
{
    /**
     * Return items for a given page
     *
     * @param int|null $page
     *
     * @return array
     */
    abstract public function getPage(int|null $page = null): array;

    /**
     * Return items of the next page by currentPage + 1
     *
     * @return array
     */
    abstract public function nextPage(): array;

    /**
     * Return items of the previous page by currentPage - 1
     *
     * @return array
     */
    abstract public function prevPage(): array;

    /**
     * Update current page
     *
     * @return AbstractPagination
     */
    abstract public function next(): AbstractPagination;

    /**
     * Update current page
     *
     * @return AbstractPagination
     */
    abstract public function prev(): AbstractPagination;

    abstract public function getItems(): array;

    abstract public function getItemCount(): int;

    abstract public function getItemsPerPage(): int;

    abstract public function getMaxPages(): int;

    abstract public function getItemClass(): ?string;

    abstract public function getCurrentPage(): int;

    abstract public function setCurrentPage(int $currentPage): AbstractPagination;

    abstract public function getLowerBound(): ?int;

    abstract public function getUpperBound(): ?int;

    abstract public function isSatisfied(): bool;

    abstract public function isFirstPage(): bool;

    abstract public function isLastPage(): bool;

    abstract public function isEmpty(): bool;

    abstract public function hasFilters(): bool;

    abstract public function getFilters(): array;

    abstract public function addFilter(callable $filter): AbstractPagination;

    abstract public function removeFilter(int $key): AbstractPagination;
}
