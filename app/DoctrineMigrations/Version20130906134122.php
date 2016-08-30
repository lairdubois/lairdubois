<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130906134122 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_core_tag_usage (tag_id INT NOT NULL, entity_type SMALLINT NOT NULL, score INT DEFAULT NULL, highlighted TINYINT(1) DEFAULT NULL, INDEX IDX_2AAC3CBFBAD26311 (tag_id), PRIMARY KEY(tag_id, entity_type)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_core_tag_usage ADD CONSTRAINT FK_2AAC3CBFBAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_core_tag_usage");
    }
}
