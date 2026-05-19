<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519204120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_news_source_preferences (id INT AUTO_INCREMENT NOT NULL, enabled TINYINT NOT NULL, user_id INT NOT NULL, news_source_id INT NOT NULL, INDEX IDX_A6B47DBEA76ED395 (user_id), INDEX IDX_A6B47DBE891943A0 (news_source_id), UNIQUE INDEX UNIQ_A6B47DBEA76ED395891943A0 (user_id, news_source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_news_source_preferences ADD CONSTRAINT FK_A6B47DBEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_news_source_preferences ADD CONSTRAINT FK_A6B47DBE891943A0 FOREIGN KEY (news_source_id) REFERENCES news_sources (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX ft_search ON news');
        $this->addSql('ALTER TABLE notifications CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_news_source_preferences DROP FOREIGN KEY FK_A6B47DBEA76ED395');
        $this->addSql('ALTER TABLE user_news_source_preferences DROP FOREIGN KEY FK_A6B47DBE891943A0');
        $this->addSql('DROP TABLE user_news_source_preferences');
        $this->addSql('CREATE FULLTEXT INDEX ft_search ON news (title, summary, content)');
        $this->addSql('ALTER TABLE notifications CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
