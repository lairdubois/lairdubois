<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140521105148 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_biography ADD htmlBody LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_core_block_text ADD htmlBody LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_blog_post ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_comment ADD htmlBody LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_faq_question ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_find ADD htmlBody LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto ADD htmlBody LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_message ADD htmlBody LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_creation ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop ADD htmlBody LONGTEXT NOT NULL, CHANGE body body LONGTEXT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_blog_post DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_core_biography DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_core_block_text DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_core_comment DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_faq_question DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_find DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_howto DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_howto_article DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_message DROP htmlBody");
        $this->addSql("ALTER TABLE tbl_wonder_creation DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop DROP htmlBody, CHANGE body body LONGTEXT DEFAULT NULL");
    }
}
