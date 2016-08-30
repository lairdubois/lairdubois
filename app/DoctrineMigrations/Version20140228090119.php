<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140228090119 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_howto CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_find CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_faq_question CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE updated_at updated_at DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_faq_question CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_find CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE updated_at updated_at DATETIME NOT NULL");
    }
}
