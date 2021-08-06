<?php
declare(strict_types=1);

namespace App\Entity;

use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Unravel the knp paginator in a manner that can be easily serialized into json.
 */
class PaginatedFeedItems
{
    private array $paginator;

    /** @var FeedItem[] */
    private iterable $items;

    public function __construct(PaginationInterface $paginator)
    {
        $this->items     = $paginator->getItems();
        $this->paginator = [
            'total'       => $paginator->getTotalItemCount(),
            'currentPage' => $paginator->getCurrentPageNumber(),
            'perPage'     => $paginator->getItemNumberPerPage(),
            'numPages'    => ceil($paginator->getTotalItemCount() / $paginator->getItemNumberPerPage()),
        ];
    }

    public function getPaginator(): array
    {
        return $this->paginator;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
