<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version2025103012122createrevokedtokenstable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE revoked_tokens (
    id INT AUTO_INCREMENT NOT NULL,
    jti CHAR(36) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE INDEX UNIQ_REVOKED_JTI (jti),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE revoked_tokens');
    }
}
