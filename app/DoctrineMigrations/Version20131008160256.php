<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131008160256 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_find ADD is_draft TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_like ADD entity_user_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_core_like ADD CONSTRAINT FK_DB0EEC1534A3E1B6 FOREIGN KEY (entity_user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("CREATE INDEX IDX_DB0EEC1534A3E1B6 ON tbl_core_like (entity_user_id)");
        $this->addSql("ALTER TABLE tbl_core_user ADD recieved_like_count INT DEFAULT NULL, ADD given_like_count INT DEFAULT NULL, ADD draft_find_count INT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_like DROP FOREIGN KEY FK_DB0EEC1534A3E1B6");
        $this->addSql("DROP INDEX IDX_DB0EEC1534A3E1B6 ON tbl_core_like");
        $this->addSql("ALTER TABLE tbl_core_like DROP entity_user_id");
        $this->addSql("ALTER TABLE tbl_core_user DROP recieved_like_count, DROP given_like_count, DROP draft_find_count");
        $this->addSql("ALTER TABLE tbl_find DROP is_draft");
    }
}
