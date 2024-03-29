<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Feed;
use App\Entity\FeedItem;
use App\Entity\PaginatedFeedItems;
use App\Entity\RssUser;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method FeedItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedItem[]    findAll()
 * @method FeedItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, FeedItem::class);
    }

    public function save(FeedItem $feedItem): void
    {
        $this->getEntityManager()->persist($feedItem);
        $this->getEntityManager()->flush();
    }

    /**
     * Deletes all items older than the given date.
     *
     * @param DateTime $date Date to delete from
     *
     * @return int Number of items deleted
     */
    public function deleteOlderThan(DateTime $date): int
    {
        $queryBuilder = $this->createQueryBuilder('fi');
        $queryBuilder
            ->delete()
            ->where('fi.lastModified < :date')
            ->setParameter(':date', $date->format(DATE_ATOM));

        return $queryBuilder->getQuery()->execute();
    }

    public function haveFeedItem(Feed $feed, string $feedItemLink): bool
    {
        $queryBuilder = $this->createQueryBuilder('fi');

        return $queryBuilder
                ->select('1')
                ->where('fi.link = :link')
                ->andWhere('fi.feed = :feed_id')
                ->setParameters([
                    ':link'    => $feedItemLink,
                    ':feed_id' => $feed->getId(),
                ])
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR) > 0;
    }

    /**
     * @param array $sortCriteria Example: ['title DESC', 'lastModified ASC']
     */
    public function findAllForUserPaginated(
        RssUser $user,
        array $sortCriteria = [],
        int $page = 1,
        int $perPage = 10
    ): PaginatedFeedItems {
        $queryBuilder = $this->createQueryBuilder('fi');

        $queryBuilder
            ->select('fi.id, fi.title, fi.excerpt, fi.image, fi.lastModified as last_modified, f.title as feed_title')
            ->innerJoin(Feed::class, 'f', Join::WITH, 'f.id = fi.feed')
            ->where('f.rssUser = :user_id')
            ->setParameter(':user_id', $user->getId());

        foreach ($sortCriteria as $criterion) {
            $queryBuilder->add('orderBy', $criterion);
        }

        return new PaginatedFeedItems($this->paginator->paginate($queryBuilder->getQuery(), $page, $perPage));
    }
}
