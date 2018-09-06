<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180906124234 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_wonder_plan ADD sketchup_3d_warehouse_url VARCHAR(255) DEFAULT NULL, ADD sketchup_3d_warehouse_embed_identifier VARCHAR(255) DEFAULT NULL, ADD a360_url VARCHAR(255) DEFAULT NULL, ADD a360_embed_identifier VARCHAR(255) DEFAULT NULL, DROP sketchup3DWarehouseUrl, DROP sketchup3DWarehouseEmbedIdentifier');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_wonder_plan ADD sketchup3DWarehouseUrl VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD sketchup3DWarehouseEmbedIdentifier VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP sketchup_3d_warehouse_url, DROP sketchup_3d_warehouse_embed_identifier, DROP a360_url, DROP a360_embed_identifier');
    }
}
