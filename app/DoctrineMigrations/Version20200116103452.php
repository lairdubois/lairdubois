<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200116103452 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_offer ADD postal_code VARCHAR(255) DEFAULT NULL, ADD geographical_areas VARCHAR(255) DEFAULT NULL, ADD formatted_adress VARCHAR(255) DEFAULT NULL, DROP postalCode, DROP geographicalAreas, DROP formattedAdress, CHANGE publishcount publish_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_offer ADD postalCode VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD geographicalAreas VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD formattedAdress VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP postal_code, DROP geographical_areas, DROP formatted_adress, CHANGE publish_count publishCount INT NOT NULL');
    }
}
