<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903195503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contributor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, birth_date DATETIME DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, biography LONGTEXT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contributor_serie (contributor_id INT NOT NULL, serie_id INT NOT NULL, INDEX IDX_3B8DB7F67A19A357 (contributor_id), INDEX IDX_3B8DB7F6D94388BD (serie_id), PRIMARY KEY(contributor_id, serie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contributor_serie ADD CONSTRAINT FK_3B8DB7F67A19A357 FOREIGN KEY (contributor_id) REFERENCES contributor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contributor_serie ADD CONSTRAINT FK_3B8DB7F6D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contributor_serie DROP FOREIGN KEY FK_3B8DB7F67A19A357');
        $this->addSql('ALTER TABLE contributor_serie DROP FOREIGN KEY FK_3B8DB7F6D94388BD');
        $this->addSql('DROP TABLE contributor');
        $this->addSql('DROP TABLE contributor_serie');
    }
}
