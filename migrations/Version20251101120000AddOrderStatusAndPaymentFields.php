<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101120000AddOrderStatusAndPaymentFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status, payment_reference and updated_at fields to orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN updated_at DATETIME DEFAULT NULL');

        // Create index on status for better query performance
        $this->addSql('CREATE INDEX idx_order_status ON orders(status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_order_status ON orders');
        $this->addSql('ALTER TABLE orders DROP COLUMN updated_at');
        $this->addSql('ALTER TABLE orders DROP COLUMN payment_reference');
        $this->addSql('ALTER TABLE orders DROP COLUMN status');
    }
}
