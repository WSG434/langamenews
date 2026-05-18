<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news_sources and news tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE news_sources (
                id INT AUTO_INCREMENT NOT NULL,
                code VARCHAR(64) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL DEFAULT 'rss',
                url VARCHAR(512) NOT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                last_fetched_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_CODE (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE news (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(512) NOT NULL,
                summary LONGTEXT DEFAULT NULL,
                content LONGTEXT DEFAULT NULL,
                published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                source VARCHAR(64) NOT NULL,
                source_uid VARCHAR(512) NOT NULL,
                fetched_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uniq_source_uid (source, source_uid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE news ADD FULLTEXT ft_search (title, summary, content)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE news_sources');
    }
}
