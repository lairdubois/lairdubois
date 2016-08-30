<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140209135450 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_knowledge_wood ADD positive_vote_count INT NOT NULL, ADD negative_vote_count INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_vote ADD parent_entity_type SMALLINT NOT NULL, ADD parent_entity_id INT NOT NULL, CHANGE group_name parent_entity_field VARCHAR(40) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_vote DROP parent_entity_type, DROP parent_entity_id, CHANGE parent_entity_field group_name VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE tbl_knowledge_wood DROP positive_vote_count, DROP negative_vote_count");
    }
}
