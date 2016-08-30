<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150724165443 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_howto ADD spotlight_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD CONSTRAINT FK_65A1D8353049EF9 FOREIGN KEY (spotlight_id) REFERENCES tbl_core_spotlight (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65A1D8353049EF9 ON tbl_howto (spotlight_id)');
        $this->addSql('ALTER TABLE tbl_core_spotlight ADD entity_type SMALLINT NOT NULL, ADD entity_id INT NOT NULL, CHANGE creation_id creation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD spotlight_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD CONSTRAINT FK_DDB282FC3049EF9 FOREIGN KEY (spotlight_id) REFERENCES tbl_core_spotlight (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DDB282FC3049EF9 ON tbl_wonder_creation (spotlight_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_spotlight DROP entity_type, DROP entity_id, CHANGE creation_id creation_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto DROP FOREIGN KEY FK_65A1D8353049EF9');
        $this->addSql('DROP INDEX UNIQ_65A1D8353049EF9 ON tbl_howto');
        $this->addSql('ALTER TABLE tbl_howto DROP spotlight_id');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP FOREIGN KEY FK_DDB282FC3049EF9');
        $this->addSql('DROP INDEX UNIQ_DDB282FC3049EF9 ON tbl_wonder_creation');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP spotlight_id');
    }
}
