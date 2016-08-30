<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140426222441 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_core_spotlight (id INT AUTO_INCREMENT NOT NULL, creation_id INT NOT NULL, created_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1BA9D9D634FFA69A (creation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_core_spotlight ADD CONSTRAINT FK_1BA9D9D634FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id)");
        $this->addSql("ALTER TABLE tbl_core_user ADD new_spotlight_email_notification_enabled TINYINT(1) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_core_spotlight");
        $this->addSql("ALTER TABLE tbl_core_user DROP new_spotlight_email_notification_enabled");
    }
}
