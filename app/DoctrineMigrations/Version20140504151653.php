<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140504151653 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_core_referer_door");
        $this->addSql("ALTER TABLE tbl_core_referer ADD route_pattern VARCHAR(100) DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_core_referer_referral ADD enabled TINYINT(1) NOT NULL, CHANGE entity_type entity_type SMALLINT DEFAULT NULL, CHANGE entity_id entity_id INT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_core_referer_door (id INT AUTO_INCREMENT NOT NULL, referer_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, path VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_2B68AEA687C61384 (referer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_core_referer_door ADD CONSTRAINT FK_2B68AEA687C61384 FOREIGN KEY (referer_id) REFERENCES tbl_core_referer (id)");
        $this->addSql("ALTER TABLE tbl_core_referer DROP route_pattern");
        $this->addSql("ALTER TABLE tbl_core_referer_referral DROP enabled, CHANGE entity_type entity_type SMALLINT NOT NULL, CHANGE entity_id entity_id INT NOT NULL");
    }
}
