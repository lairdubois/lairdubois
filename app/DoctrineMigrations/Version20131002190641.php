<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131002190641 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL, CHANGE location location VARCHAR(100) DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_find CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE title title VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_user CHANGE location location VARCHAR(100) DEFAULT NULL, CHANGE facebook facebook VARCHAR(50) DEFAULT NULL, CHANGE twitter twitter VARCHAR(50) DEFAULT NULL, CHANGE googleplus googleplus VARCHAR(50) DEFAULT NULL, CHANGE youtube youtube VARCHAR(50) DEFAULT NULL, CHANGE pinterest pinterest VARCHAR(50) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_user CHANGE facebook facebook VARCHAR(255) DEFAULT NULL, CHANGE twitter twitter VARCHAR(255) DEFAULT NULL, CHANGE googleplus googleplus VARCHAR(255) DEFAULT NULL, CHANGE youtube youtube VARCHAR(255) DEFAULT NULL, CHANGE pinterest pinterest VARCHAR(255) DEFAULT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_find CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE title title VARCHAR(255) DEFAULT '' NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL");
    }
}
