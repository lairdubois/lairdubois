<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140424152122 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_find ADD main_picture_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_find ADD CONSTRAINT FK_7C8E31ECD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)");
        $this->addSql("CREATE INDEX IDX_7C8E31ECD6BDC9DC ON tbl_find (main_picture_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_find DROP FOREIGN KEY FK_7C8E31ECD6BDC9DC");
        $this->addSql("DROP INDEX IDX_7C8E31ECD6BDC9DC ON tbl_find");
        $this->addSql("ALTER TABLE tbl_find DROP main_picture_id");
    }
}
