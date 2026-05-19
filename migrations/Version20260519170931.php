<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519170931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news ADD url VARCHAR(2048) DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE fetched_at fetched_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX ft_search ON news');
        $this->addSql('ALTER TABLE news ADD FULLTEXT ft_search (title, summary, content)');
        $this->addSql('ALTER TABLE news_sources CHANGE type type VARCHAR(20) NOT NULL, CHANGE enabled enabled TINYINT NOT NULL, CHANGE last_fetched_at last_fetched_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE news_sources RENAME INDEX uniq_code TO UNIQ_85B9791477153098');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news DROP url, CHANGE published_at published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE fetched_at fetched_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE FULLTEXT INDEX ft_search ON news (title, summary, content)');
        $this->addSql('ALTER TABLE news_sources CHANGE type type VARCHAR(20) DEFAULT \'rss\' NOT NULL, CHANGE enabled enabled TINYINT DEFAULT 1 NOT NULL, CHANGE last_fetched_at last_fetched_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE news_sources RENAME INDEX uniq_85b9791477153098 TO UNIQ_CODE');
    }
}
