<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130529132926 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_wonder_howto_plan (howto_id INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_CE13627EFBE2D86A (howto_id), INDEX IDX_CE13627EE899029B (plan_id), PRIMARY KEY(howto_id, plan_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_wonder_howto_plan ADD CONSTRAINT FK_CE13627EFBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_howto_plan ADD CONSTRAINT FK_CE13627EE899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD howto_count INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto ADD plan_count INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_wonder_howto_plan");
        $this->addSql("ALTER TABLE tbl_howto DROP plan_count");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP howto_count");
    }
}
