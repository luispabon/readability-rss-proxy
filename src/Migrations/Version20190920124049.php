<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190920124049 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE feed_item ADD feed_id INT NOT NULL');
        $this->addSql('ALTER TABLE feed_item ADD CONSTRAINT FK_9F8CCE4951A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9F8CCE4951A5BC03 ON feed_item (feed_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE feed_item DROP CONSTRAINT FK_9F8CCE4951A5BC03');
        $this->addSql('DROP INDEX IDX_9F8CCE4951A5BC03');
        $this->addSql('ALTER TABLE feed_item DROP feed_id');
    }
}
