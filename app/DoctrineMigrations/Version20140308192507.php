<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140308192507 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_wonder_plan_resource (plan_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_A7FA793E899029B (plan_id), INDEX IDX_A7FA79389329D25 (resource_id), PRIMARY KEY(plan_id, resource_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_wonder_plan_resource ADD CONSTRAINT FK_A7FA793E899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_plan_resource ADD CONSTRAINT FK_A7FA79389329D25 FOREIGN KEY (resource_id) REFERENCES tbl_core_resource (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD kinds LONGTEXT NOT NULL COMMENT '(DC2Type:simple_array)', ADD zip_archive_size INT NOT NULL, DROP kind");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_wonder_plan_resource");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD kind SMALLINT NOT NULL, DROP kinds, DROP zip_archive_size");
    }
}
