<?php

namespace SkriptManufaktur\SimpleRestBundle\Pagination;

use SkriptManufaktur\SimpleRestBundle\Exception\PaginationException;

class Pagination extends AbstractPagination
{
    protected bool $booted = false;
    protected array $items = [];
    protected int|null $itemCount = null;
    protected int|null $itemsPerPage = null;
    protected int|null $maxPages = null;
    protected string|null $itemClass = null;
    protected int $currentPage = 0;
    protected int|null $lowerBound = null;
    protected int|null $upperBound = null;
    protected bool $isSatisfied = false;
    protected bool $isFirstPage = true;
    protected bool $isLastPage = false;
    protected bool $isEmpty = true;
    protected array $filters = [];


    public function __construct(array $items, int $perPage)
    {
        $this->items = $items;
        $this->itemsPerPage = $perPage;

        if (!empty($items)) {
            $firstItem = current($items);
            $firstItemClass = $this->testItemClass($firstItem);

            foreach ($this->items as $key => $it) {
                $itItemClass = $this->testItemClass($it);

                if ($itItemClass !== $firstItemClass) {
                    throw new PaginationException(sprintf(
                        'Expected class for item with key %s is "%s". Got "%s"...',
                        $key,
                        $firstItemClass,
                        $itItemClass
                    ));
                }
            }

            $this->itemClass = $firstItemClass;
        }
    }

    public function boot(): AbstractPagination
    {
        // trace all filters to get the main result
        $items = $this->filter($this->items);

        // calc stats by remaining items
        $this->items = $items;
        $this->itemCount = count($items);
        $this->maxPages = (int) ceil($this->itemCount / $this->itemsPerPage);
        $this->isEmpty = 0 === $this->itemCount;

        // set this pagination as booted
        $this->booted = true;

        return $this;
    }

    /**
     * Return items for a given page
     *
     * @param int|null $page
     *
     * @return array
     */
    public function getPage(int|null $page = null): array
    {
        // take the current page, if not given
        if (null !== $page) {
            $this->currentPage = $page;
        }

        // boot, if not done yet or something has changed
        if (!$this->booted) {
            $this->boot();
        }

        // slice the page out of all items
        $slice = array_slice($this->items, $this->currentPage * $this->itemsPerPage, $this->itemsPerPage, true);
        $sliceWidth = count($slice);

        // calculate more stats
        $this->lowerBound = $this->currentPage * $this->itemsPerPage + 1;
        $this->upperBound = $this->lowerBound + $sliceWidth - 1;
        $this->isSatisfied = $sliceWidth === $this->itemsPerPage;
        $this->definePage();

        return $slice;
    }

    /**
     * Return items of the next page by currentPage + 1
     *
     * @return array
     */
    public function nextPage(): array
    {
        return $this->next()->getPage();
    }

    /**
     * Return items of the previous page by currentPage - 1
     *
     * @return array
     */
    public function prevPage(): array
    {
        return $this->prev()->getPage();
    }

    /**
     * Update current page
     *
     * @return Pagination
     */
    public function next(): AbstractPagination
    {
        if ($this->currentPage < $this->maxPages) {
            $this->currentPage++;
        }

        $this->definePage();

        return $this;
    }

    /**
     * Update current page
     *
     * @return Pagination
     */
    public function prev(): AbstractPagination
    {
        if ($this->currentPage > 0) {
            $this->currentPage--;
        }

        $this->definePage();

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemCount(): int
    {
        if (null === $this->itemCount) {
            throw new PaginationException('Pagination was not booted, yet.');
        }

        return $this->itemCount;
    }

    public function getItemsPerPage(): int
    {
        if (null === $this->itemsPerPage) {
            throw new PaginationException('Pagination was not booted, yet.');
        }

        return $this->itemsPerPage;
    }

    public function getMaxPages(): int
    {
        if (null === $this->maxPages) {
            throw new PaginationException('Pagination was not booted, yet.');
        }

        return $this->maxPages;
    }

    public function getItemClass(): string|null
    {
        if (null === $this->itemClass) {
            throw new PaginationException('Pagination was not booted, yet.');
        }

        return $this->itemClass;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): AbstractPagination
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function getLowerBound(): int|null
    {
        return $this->lowerBound;
    }

    public function getUpperBound(): int|null
    {
        return $this->upperBound;
    }

    public function isSatisfied(): bool
    {
        return $this->isSatisfied;
    }

    public function isFirstPage(): bool
    {
        return $this->isFirstPage;
    }

    public function isLastPage(): bool
    {
        return $this->isLastPage;
    }

    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addFilter(callable $filter): AbstractPagination
    {
        $this->filters[] = $filter;
        $this->booted = false;

        return $this;
    }

    public function removeFilter(int $key): AbstractPagination
    {
        if (isset($this->filters[$key])) {
            unset($this->filters[$key]);
            $this->booted = false;
        }

        return $this;
    }

    /**
     * Use all filters on the items
     *
     * @param array $items
     *
     * @return array
     */
    protected function filter(array $items): array
    {
        foreach ($this->filters as $filter) {
            $items = array_filter($items, $filter);
        }

        return $items;
    }

    /**
     * Setting first/last page
     */
    protected function definePage(): void
    {
        $this->isFirstPage = 0 === $this->currentPage;
        $this->isLastPage = ($this->maxPages - 1) === $this->currentPage; // as the current page starts with 0
    }

    /**
     * return some name of class for a given object
     *
     * @param mixed $item
     *
     * @return string
     */
    protected function testItemClass(mixed $item): string
    {
        // if Doctrine uses its Proxy-Classes, give it a try
        if (is_object($item)) {
            return str_replace('Proxies\\__CG__\\', '', get_class($item));
        }

        if (is_array($item)) {
            return 'array';
        }

        return 'string';
    }
}
