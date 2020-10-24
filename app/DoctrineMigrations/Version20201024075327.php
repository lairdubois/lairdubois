<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201024075327 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_opencutlist_access (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, kind SMALLINT NOT NULL, env SMALLINT NOT NULL, client_ip4 VARCHAR(255) NOT NULL, client_user_agent VARCHAR(255) DEFAULT NULL, client_ocl_version VARCHAR(15) DEFAULT NULL, analyzed TINYINT(1) NOT NULL, count_code VARCHAR(2) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, client_os SMALLINT NOT NULL, client_sketchup_family SMALLINT NOT NULL, client_sketchup_version VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_opencutlist_access');
    }
}
