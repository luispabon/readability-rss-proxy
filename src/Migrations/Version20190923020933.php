<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190923020933 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE feed_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE feed_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE rss_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE feed (id INT NOT NULL, rss_user_id INT NOT NULL, feed_url TEXT NOT NULL, title TEXT DEFAULT NULL, description TEXT DEFAULT NULL, icon TEXT DEFAULT NULL, last_modified TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_234044ABAF9D76D4 ON feed (rss_user_id)');
        $this->addSql('CREATE TABLE feed_item (id INT NOT NULL, feed_id INT NOT NULL, title TEXT NOT NULL, description TEXT NOT NULL, link TEXT NOT NULL, last_modified TIMESTAMP(0) WITH TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9F8CCE4951A5BC03 ON feed_item (feed_id)');
        $this->addSql('CREATE INDEX idx_link ON feed_item (link)');
        $this->addSql('CREATE TABLE rss_user (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CDF5AD12E7927C74 ON rss_user (email)');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT FK_234044ABAF9D76D4 FOREIGN KEY (rss_user_id) REFERENCES rss_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feed_item ADD CONSTRAINT FK_9F8CCE4951A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE feed_item DROP CONSTRAINT FK_9F8CCE4951A5BC03');
        $this->addSql('ALTER TABLE feed DROP CONSTRAINT FK_234044ABAF9D76D4');
        $this->addSql('DROP SEQUENCE feed_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE feed_item_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE rss_user_id_seq CASCADE');
        $this->addSql('DROP TABLE feed');
        $this->addSql('DROP TABLE feed_item');
        $this->addSql('DROP TABLE rss_user');
    }
}
