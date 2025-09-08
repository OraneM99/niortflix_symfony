<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907205516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user 
        ADD is_active TINYINT(1) NOT NULL DEFAULT 1,
        ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
    ");

        $this->addSql("UPDATE user SET created_at = NOW() WHERE created_at IS NULL");
        $this->addSql("ALTER TABLE user 
        MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
    ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user DROP is_active, DROP created_at");
    }
}
