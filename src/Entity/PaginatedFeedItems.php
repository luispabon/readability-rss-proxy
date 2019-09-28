<?php
declare(strict_types=1);

namespace App\Entity;

use Knp\Component\Pager\Pagination\PaginationInterface;

class PaginatedFeedItems
{
    /** @var array|FeedItem[] */
    private $items = [];

    /** @var array */
    private $paginator;

    public function __construct(PaginationInterface $paginator)
    {
        $this->items     = $paginator->getItems();
        $this->paginator = [
            'total'       => $paginator->getTotalItemCount(),
            'currentPage' => $paginator->getCurrentPageNumber(),
            'perPage'     => $paginator->getItemNumberPerPage(),
        ];
    }

    /**
     * @return FeedItem[]|array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param FeedItem[]|array $items
     *
     * @return PaginatedFeedItems
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function getPaginator(): array
    {
        return $this->paginator;
    }

    /**
     * @param array $paginator
     *
     * @return PaginatedFeedItems
     */
    public function setPaginator(array $paginator): PaginatedFeedItems
    {
        $this->paginator = $paginator;

        return $this;
    }
}
