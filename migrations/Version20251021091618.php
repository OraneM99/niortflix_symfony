<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021091618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_progress (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tmdb_id INT NOT NULL, season_number INT NOT NULL, episode_number INT NOT NULL, watched TINYINT(1) NOT NULL, progress INT DEFAULT NULL, watched_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C28C1646A76ED395 (user_id), UNIQUE INDEX user_episode_unique (user_id, tmdb_id, season_number, episode_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_C28C1646A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_C28C1646A76ED395');
        $this->addSql('DROP TABLE user_progress');
    }
}
