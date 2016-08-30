<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131102145910 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_wonder_creation_inspiration (creation_id INT NOT NULL, rebound_creation_id INT NOT NULL, INDEX IDX_A210241F34FFA69A (creation_id), INDEX IDX_A210241F68713626 (rebound_creation_id), PRIMARY KEY(creation_id, rebound_creation_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_plan_inspiration (plan_id INT NOT NULL, rebound_plan_id INT NOT NULL, INDEX IDX_CC365C75E899029B (plan_id), INDEX IDX_CC365C75FD3933A7 (rebound_plan_id), PRIMARY KEY(plan_id, rebound_plan_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_wonder_creation_inspiration ADD CONSTRAINT FK_A210241F34FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id)");
        $this->addSql("ALTER TABLE tbl_wonder_creation_inspiration ADD CONSTRAINT FK_A210241F68713626 FOREIGN KEY (rebound_creation_id) REFERENCES tbl_wonder_creation (id)");
        $this->addSql("ALTER TABLE tbl_wonder_plan_inspiration ADD CONSTRAINT FK_CC365C75E899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id)");
        $this->addSql("ALTER TABLE tbl_wonder_plan_inspiration ADD CONSTRAINT FK_CC365C75FD3933A7 FOREIGN KEY (rebound_plan_id) REFERENCES tbl_wonder_plan (id)");
        $this->addSql("ALTER TABLE tbl_wonder_creation ADD rebound_count INT NOT NULL, ADD inspiration_count INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD rebound_count INT NOT NULL, ADD inspiration_count INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_wonder_creation_inspiration");
        $this->addSql("DROP TABLE tbl_wonder_plan_inspiration");
        $this->addSql("ALTER TABLE tbl_wonder_creation DROP rebound_count, DROP inspiration_count");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP rebound_count, DROP inspiration_count");
    }
}
