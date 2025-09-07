<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907163238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_serie DROP FOREIGN KEY FK_48F8686C5298BC78');
        $this->addSql('DROP INDEX IDX_48F8686C5298BC78 ON user_serie');
        $this->addSql('ALTER TABLE user_serie DROP user_series_id, CHANGE serie_id serie_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_serie ADD user_series_id INT DEFAULT NULL, CHANGE serie_id serie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_serie ADD CONSTRAINT FK_48F8686C5298BC78 FOREIGN KEY (user_series_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_48F8686C5298BC78 ON user_serie (user_series_id)');
    }
}
