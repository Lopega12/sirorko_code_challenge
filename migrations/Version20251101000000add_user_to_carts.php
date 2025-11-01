<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101000000AddUserToCarts extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id to carts and unique index to ensure one cart per user (portable via Schema API)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('carts')) {
            return;
        }

        $table = $schema->getTable('carts');

        if (!$table->hasColumn('user_id')) {
            $table->addColumn('user_id', 'string', ['length' => 36, 'notnull' => false]);
        }

        // add unique index on user_id (Doctrine will generate the proper SQL for the underlying platform)
        if (!$table->hasIndex('UNIQ_CART_USER')) {
            $table->addUniqueIndex(['user_id'], 'UNIQ_CART_USER');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('carts')) {
            return;
        }

        $table = $schema->getTable('carts');

        if ($table->hasIndex('UNIQ_CART_USER')) {
            $table->dropIndex('UNIQ_CART_USER');
        }

        if ($table->hasColumn('user_id')) {
            $table->dropColumn('user_id');
        }
    }
}
