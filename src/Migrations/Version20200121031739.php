<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200121031739 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE point (id INT AUTO_INCREMENT NOT NULL, track_id INT DEFAULT NULL, route_id INT DEFAULT NULL, trip_id INT DEFAULT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, elevation NUMERIC(10, 2) DEFAULT NULL, time DATETIME DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_B7A5F3245ED23C43 (track_id), INDEX IDX_B7A5F32434ECB4E6 (route_id), INDEX IDX_B7A5F324A5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE point ADD CONSTRAINT FK_B7A5F3245ED23C43 FOREIGN KEY (track_id) REFERENCES track (id)');
        $this->addSql('ALTER TABLE point ADD CONSTRAINT FK_B7A5F32434ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id)');
        $this->addSql('ALTER TABLE point ADD CONSTRAINT FK_B7A5F324A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE point');
    }
}
