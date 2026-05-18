<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518210614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE confirmation_codes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(6) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, confirmed_at DATETIME DEFAULT NULL, expires_at DATETIME NOT NULL, attempts INT NOT NULL, user_id INT NOT NULL, INDEX IDX_7AC5D2E1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE confirmation_codes ADD CONSTRAINT FK_7AC5D2E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD telegram_handle VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE confirmation_codes DROP FOREIGN KEY FK_7AC5D2E1A76ED395');
        $this->addSql('DROP TABLE confirmation_codes');
        $this->addSql('ALTER TABLE users DROP telegram_handle');
    }
}
