<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make email and password nullable for Telegram-only accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY email VARCHAR(180) DEFAULT NULL, MODIFY password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY email VARCHAR(180) NOT NULL, MODIFY password VARCHAR(255) NOT NULL');
    }
}
