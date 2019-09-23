<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Repository\RssUserRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\AbstractQuery;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190923144706 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        /** @var RssUserRepository $rssUserRepository */
        $rssUserRepository = $this->container->get('migrations.rssUserRepository');

        $this->addSql('ALTER TABLE rss_user ADD opml_token VARCHAR(255)');

        $users = $rssUserRepository->createQueryBuilder('u')
                                   ->select(['u.id'])
                                   ->getQuery()
                                   ->getResult(AbstractQuery::HYDRATE_ARRAY);

        foreach ($users as $user) {
            $this->addSql(
                'UPDATE rss_user SET opml_token = :token WHERE id = :id',
                [
                    ':token' => Uuid::uuid4()->toString(),
                    ':id'    => $user['id'],
                ]
            );
        }

        $this->addSql('ALTER TABLE rss_user ALTER COLUMN opml_token SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE rss_user DROP opml_token');
    }
}
