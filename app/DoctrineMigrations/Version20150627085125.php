<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150627085125 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_faq_question ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_find ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto_article ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge_wood ADD changed_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD changed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD changed_at DATETIME NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post DROP changed_at');
        $this->addSql('ALTER TABLE tbl_faq_question DROP changed_at');
        $this->addSql('ALTER TABLE tbl_find DROP changed_at');
        $this->addSql('ALTER TABLE tbl_howto DROP changed_at');
        $this->addSql('ALTER TABLE tbl_howto_article DROP changed_at');
        $this->addSql('ALTER TABLE tbl_knowledge_wood DROP changed_at, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP changed_at');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP changed_at');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP changed_at');
    }
}
