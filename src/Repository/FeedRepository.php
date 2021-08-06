<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Feed;
use App\Entity\RssUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Feed|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feed|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feed[]    findAll()
 * @method Feed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feed::class);
    }

    public function save(Feed $feed): void
    {
        $this->getEntityManager()->persist($feed);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Feed[]
     */
    public function findForUser(RssUser $user, array $sortCriteria = ['id' => 'ASC']): array
    {
        return $this->findBy(['rssUser' => $user], $sortCriteria);
    }
}
