<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151203092123 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post ADD body_block_picture_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_faq_question ADD body_block_picture_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto_article ADD body_block_picture_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD body_block_picture_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD body_block_picture_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post DROP body_block_picture_count');
        $this->addSql('ALTER TABLE tbl_faq_question DROP body_block_picture_count');
        $this->addSql('ALTER TABLE tbl_howto_article DROP body_block_picture_count');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP body_block_picture_count');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP body_block_picture_count');
    }
}
