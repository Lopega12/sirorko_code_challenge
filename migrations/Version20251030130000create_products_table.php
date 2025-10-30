<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030130000CreateProductsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products table with UUID id, sku unique, name, description, price and currency, stock and timestamps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
    id CHAR(36) NOT NULL,
    sku VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price NUMERIC(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    stock INT NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE INDEX UNIQ_PRODUCTS_SKU (sku)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE products');
    }
}
