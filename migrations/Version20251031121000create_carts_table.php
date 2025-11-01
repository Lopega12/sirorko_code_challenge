<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251031121000CreateCartsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create carts table to store active carts (items as JSON)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE carts (
    id CHAR(36) NOT NULL,
    items JSON NOT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE carts');
    }
}
