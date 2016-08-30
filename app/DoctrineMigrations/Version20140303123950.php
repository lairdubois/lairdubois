<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140303123950 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_knowledge_value_integer ADD source_type SMALLINT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_knowledge_value_picture ADD source_type SMALLINT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_knowledge_value_string ADD source_type SMALLINT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_knowledge_value_integer DROP source_type");
        $this->addSql("ALTER TABLE tbl_knowledge_value_picture DROP source_type");
        $this->addSql("ALTER TABLE tbl_knowledge_value_string DROP source_type");
    }
}
