<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\RssUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method RssUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method RssUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method RssUser[]    findAll()
 * @method RssUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RssUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RssUser::class);
    }
}
