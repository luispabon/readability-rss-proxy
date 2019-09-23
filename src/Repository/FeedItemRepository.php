<?php
declare(strict_types=1);

namespace App\Repository;

//use App\Entity\Feed;
use App\Entity\Feed;
use App\Entity\FeedItem;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

/**
 * @method FeedItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedItem[]    findAll()
 * @method FeedItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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
}
