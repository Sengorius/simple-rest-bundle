<?php

namespace SkriptManufaktur\SimpleRestBundle\Pagination;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use SkriptManufaktur\SimpleRestBundle\Exception\PaginationException;

class SinglePage extends Pagination
{
    protected int $maxItems = 0;


    public function __construct(array $items, int $perPage, int $page, int $maxItems)
    {
        parent::__construct($items, $perPage);

        $this->maxItems = $maxItems;
        $this->currentPage = $page;
    }

    /**
     * @param Paginator $paginator
     * @param int       $perPage
     * @param int       $page
     *
     * @return SinglePage
     *
     * @throws Exception
     */
    public static function fromDoctrinePaginator(Paginator $paginator, int $perPage, int $page): SinglePage
    {
        $items = iterator_to_array($paginator->getIterator());
        $maxItems = $paginator->count();

        return new self($items, $perPage, $page, $maxItems);
    }

    public function boot(): AbstractPagination
    {
        // trace all filters to get the main result
        // and calc the difference between filtered items
        $currentItemCount = count($this->items);
        $items = parent::filter($this->items);
        $filteredItems = $currentItemCount - count($items);

        // calc stats by remaining items
        $actualItemCount = count($items);
        $this->items = $items;
        $this->itemCount = $this->maxItems - $filteredItems;
        $this->maxPages = (int) ceil($this->itemCount / $this->itemsPerPage);
        $this->isEmpty = 0 === $actualItemCount;

        // calculate more stats
        $this->lowerBound = $this->currentPage * $this->itemsPerPage + 1;
        $this->upperBound = $this->lowerBound + $actualItemCount - 1;
        $this->isSatisfied = $actualItemCount === $this->itemsPerPage;
        $this->definePage();

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
     *
     * @throws PaginationException
     */
    public function getPage(?int $page = null): array
    {
        // take the current page, if not given
        if (null !== $page && $page !== $this->currentPage) {
            throw new PaginationException(sprintf('This single page has only page %d stored, getting page %d is not possible!', $this->currentPage, $page));
        }

        // boot, if not done yet or something has changed
        if (!$this->booted) {
            $this->boot();
        }

        return $this->items;
    }

    /**
     * @return AbstractPagination
     *
     * @throws PaginationException
     */
    public function next(): AbstractPagination
    {
        throw new PaginationException('Method next() is not usable for a single page!');
    }

    /**
     * @return AbstractPagination
     *
     * @throws PaginationException
     */
    public function prev(): AbstractPagination
    {
        throw new PaginationException('Method prev() is not usable for a single page!');
    }

    /**
     * @return array
     *
     * @throws PaginationException
     */
    public function nextPage(): array
    {
        throw new PaginationException('Method nextPage() is not usable for a single page!');
    }

    /**
     * @return array
     *
     * @throws PaginationException
     */
    public function prevPage(): array
    {
        throw new PaginationException('Method prevPage() is not usable for a single page!');
    }
}
