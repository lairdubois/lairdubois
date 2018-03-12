<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180312101917 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post_body_block DROP FOREIGN KEY FK_6D9AC0DEE9ED820C');
        $this->addSql('ALTER TABLE tbl_blog_post_body_block ADD CONSTRAINT FK_6D9AC0DEE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_faq_question_body_block DROP FOREIGN KEY FK_3FD3049BE9ED820C');
        $this->addSql('ALTER TABLE tbl_faq_question_body_block ADD CONSTRAINT FK_3FD3049BE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_find_body_block DROP FOREIGN KEY FK_BCCF64F6E9ED820C');
        $this->addSql('ALTER TABLE tbl_find_body_block ADD CONSTRAINT FK_BCCF64F6E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_article_body_block DROP FOREIGN KEY FK_CA244334E9ED820C');
        $this->addSql('ALTER TABLE tbl_howto_article_body_block ADD CONSTRAINT FK_CA244334E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block DROP FOREIGN KEY FK_2136A598E9ED820C');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block ADD CONSTRAINT FK_2136A598E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block DROP FOREIGN KEY FK_DA8BFF89E9ED820C');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block ADD CONSTRAINT FK_DA8BFF89E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_body_block DROP FOREIGN KEY FK_D91A44D3E9ED820C');
        $this->addSql('ALTER TABLE tbl_wonder_creation_body_block ADD CONSTRAINT FK_D91A44D3E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_body_block DROP FOREIGN KEY FK_DE6F6E5CE9ED820C');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_body_block ADD CONSTRAINT FK_DE6F6E5CE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post_body_block DROP FOREIGN KEY FK_6D9AC0DEE9ED820C');
        $this->addSql('ALTER TABLE tbl_blog_post_body_block ADD CONSTRAINT FK_6D9AC0DEE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_faq_question_body_block DROP FOREIGN KEY FK_3FD3049BE9ED820C');
        $this->addSql('ALTER TABLE tbl_faq_question_body_block ADD CONSTRAINT FK_3FD3049BE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_find_body_block DROP FOREIGN KEY FK_BCCF64F6E9ED820C');
        $this->addSql('ALTER TABLE tbl_find_body_block ADD CONSTRAINT FK_BCCF64F6E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_howto_article_body_block DROP FOREIGN KEY FK_CA244334E9ED820C');
        $this->addSql('ALTER TABLE tbl_howto_article_body_block ADD CONSTRAINT FK_CA244334E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block DROP FOREIGN KEY FK_2136A598E9ED820C');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block ADD CONSTRAINT FK_2136A598E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block DROP FOREIGN KEY FK_DA8BFF89E9ED820C');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block ADD CONSTRAINT FK_DA8BFF89E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_wonder_creation_body_block DROP FOREIGN KEY FK_D91A44D3E9ED820C');
        $this->addSql('ALTER TABLE tbl_wonder_creation_body_block ADD CONSTRAINT FK_D91A44D3E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_body_block DROP FOREIGN KEY FK_DE6F6E5CE9ED820C');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_body_block ADD CONSTRAINT FK_DE6F6E5CE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
    }
}
