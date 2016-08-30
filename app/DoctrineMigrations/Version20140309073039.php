<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140309073039 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP FOREIGN KEY FK_22AAE7D89329D25");
        $this->addSql("DROP INDEX UNIQ_22AAE7D89329D25 ON tbl_wonder_plan");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP resource_id");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD resource_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD CONSTRAINT FK_22AAE7D89329D25 FOREIGN KEY (resource_id) REFERENCES tbl_core_resource (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_22AAE7D89329D25 ON tbl_wonder_plan (resource_id)");
    }
}
