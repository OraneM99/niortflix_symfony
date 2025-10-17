<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017204419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE episode (id INT AUTO_INCREMENT NOT NULL, season_id INT NOT NULL, episode_number INT NOT NULL, name VARCHAR(255) NOT NULL, overview LONGTEXT DEFAULT NULL, air_date DATE DEFAULT NULL, runtime INT DEFAULT NULL, still_path VARCHAR(255) DEFAULT NULL, tmdb_id INT DEFAULT NULL, vote DOUBLE PRECISION DEFAULT NULL, INDEX IDX_DDAA1CDA4EC001D1 (season_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, serie_id INT NOT NULL, season_number INT NOT NULL, name VARCHAR(255) NOT NULL, overview LONGTEXT DEFAULT NULL, air_date DATE DEFAULT NULL, episode_count INT DEFAULT NULL, poster_path VARCHAR(255) DEFAULT NULL, INDEX IDX_F0E45BA9D94388BD (serie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_episode (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, episode_id INT NOT NULL, watched TINYINT(1) NOT NULL, watched_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', rating INT DEFAULT NULL, INDEX IDX_85A702D0A76ED395 (user_id), INDEX IDX_85A702D0362B62A0 (episode_id), UNIQUE INDEX user_episode_unique (user_id, episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE episode ADD CONSTRAINT FK_DDAA1CDA4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA9D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie DROP original_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE episode DROP FOREIGN KEY FK_DDAA1CDA4EC001D1');
        $this->addSql('ALTER TABLE season DROP FOREIGN KEY FK_F0E45BA9D94388BD');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0A76ED395');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0362B62A0');
        $this->addSql('DROP TABLE episode');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE user_episode');
        $this->addSql('ALTER TABLE serie ADD original_name VARCHAR(100) DEFAULT NULL');
    }
}
