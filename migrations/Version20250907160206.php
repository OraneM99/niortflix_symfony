<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907160206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_serie (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, serie_id INT DEFAULT NULL, user_series_id INT DEFAULT NULL, user_status VARCHAR(20) DEFAULT NULL, progression INT DEFAULT NULL, INDEX IDX_48F8686CA76ED395 (user_id), INDEX IDX_48F8686CD94388BD (serie_id), INDEX IDX_48F8686C5298BC78 (user_series_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_serie ADD CONSTRAINT FK_48F8686CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_serie ADD CONSTRAINT FK_48F8686CD94388BD FOREIGN KEY (serie_id) REFERENCES serie (id)');
        $this->addSql('ALTER TABLE user_serie ADD CONSTRAINT FK_48F8686C5298BC78 FOREIGN KEY (user_series_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_serie DROP FOREIGN KEY FK_48F8686CA76ED395');
        $this->addSql('ALTER TABLE user_serie DROP FOREIGN KEY FK_48F8686CD94388BD');
        $this->addSql('ALTER TABLE user_serie DROP FOREIGN KEY FK_48F8686C5298BC78');
        $this->addSql('DROP TABLE user_serie');
    }
}
