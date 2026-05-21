<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on telegram_handle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E994FCB66 ON users');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E994FCB66 ON users (telegram_handle)');
    }
}
