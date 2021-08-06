<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\RssUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @method RssUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method RssUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method RssUser[]    findAll()
 * @method RssUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RssUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private UserPasswordHasherInterface $passwordEncoder)
    {
        parent::__construct($registry, RssUser::class);
    }

    public function makeUser(string $email, string $password, bool $makeAdmin): RssUser
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('`%s` is not a valid email address', $email));
        }

        if (trim($password) === '') {
            throw new InvalidArgumentException('Empty passwords are not allowed');
        }

        $roles = ['ROLE_USER'];
        if ($makeAdmin === true) {
            $roles[] = 'ROLE_ADMIN';
        }

        $user = (new RssUser())
            ->setEmail($email)
            ->setRoles($roles)
            ->setOpmlToken(Uuid::uuid4()->toString());

        $user->setPassword($this->passwordEncoder->hashPassword($user, $password));

        $this->save($user);

        return $user;
    }

    public function save(RssUser $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByIdAndOpmlToken(int $id, string $opmlToken): ?RssUser
    {
        return $this->findOneBy(['id' => $id, 'opmlToken' => $opmlToken]);
    }
}
