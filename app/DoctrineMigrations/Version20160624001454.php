<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160624001454 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_spotlight DROP FOREIGN KEY FK_1BA9D9D634FFA69A');
        $this->addSql('DROP INDEX UNIQ_1BA9D9D634FFA69A ON tbl_core_spotlight');
        $this->addSql('ALTER TABLE tbl_core_spotlight DROP creation_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_spotlight ADD creation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_spotlight ADD CONSTRAINT FK_1BA9D9D634FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1BA9D9D634FFA69A ON tbl_core_spotlight (creation_id)');
    }
}
