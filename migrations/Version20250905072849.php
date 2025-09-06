<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905072849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE film (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, overview LONGTEXT DEFAULT NULL, vote DOUBLE PRECISION DEFAULT NULL, popularity DOUBLE PRECISION DEFAULT NULL, air_date DATETIME NOT NULL, backdrop VARCHAR(255) DEFAULT NULL, poster VARCHAR(255) DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, streaming_links LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE film_genre (film_id INT NOT NULL, genre_id INT NOT NULL, INDEX IDX_1A3CCDA8567F5183 (film_id), INDEX IDX_1A3CCDA84296D31F (genre_id), PRIMARY KEY(film_id, genre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE film_contributor (film_id INT NOT NULL, contributor_id INT NOT NULL, INDEX IDX_A2D7BA9C567F5183 (film_id), INDEX IDX_A2D7BA9C7A19A357 (contributor_id), PRIMARY KEY(film_id, contributor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE film_genre ADD CONSTRAINT FK_1A3CCDA8567F5183 FOREIGN KEY (film_id) REFERENCES film (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_genre ADD CONSTRAINT FK_1A3CCDA84296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_contributor ADD CONSTRAINT FK_A2D7BA9C567F5183 FOREIGN KEY (film_id) REFERENCES film (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_contributor ADD CONSTRAINT FK_A2D7BA9C7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributor (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE film_genre DROP FOREIGN KEY FK_1A3CCDA8567F5183');
        $this->addSql('ALTER TABLE film_genre DROP FOREIGN KEY FK_1A3CCDA84296D31F');
        $this->addSql('ALTER TABLE film_contributor DROP FOREIGN KEY FK_A2D7BA9C567F5183');
        $this->addSql('ALTER TABLE film_contributor DROP FOREIGN KEY FK_A2D7BA9C7A19A357');
        $this->addSql('DROP TABLE film');
        $this->addSql('DROP TABLE film_genre');
        $this->addSql('DROP TABLE film_contributor');
    }
}
