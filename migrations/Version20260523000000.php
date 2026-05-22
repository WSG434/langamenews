<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore FULLTEXT index on news (dropped without recreation in Version20260519204120)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news ADD FULLTEXT ft_search (title, summary, content)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX ft_search ON news');
    }
}
