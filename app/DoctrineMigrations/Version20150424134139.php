<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150424134139 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post DROP htmlBody');
        $this->addSql('ALTER TABLE tbl_faq_question DROP htmlBody');
        $this->addSql('ALTER TABLE tbl_howto_article ADD headings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', DROP htmlBody');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP htmlBody');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP htmlBody');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_faq_question ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_howto_article ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci, DROP headings');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
    }
}
