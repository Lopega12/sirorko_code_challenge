<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251031120000CreateOrdersTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create normalized orders and order_items tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE orders (
    id CHAR(36) NOT NULL,
    cart_id CHAR(36) NOT NULL,
    total NUMERIC(10,2) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;');

        $this->addSql('CREATE TABLE order_items (
    id INT AUTO_INCREMENT NOT NULL,
    order_id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    price NUMERIC(10,2) NOT NULL,
    quantity INT NOT NULL,
    INDEX IDX_ORDER_ITEMS_ORDER_ID (order_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_ORDER_ITEMS_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
    }
}
