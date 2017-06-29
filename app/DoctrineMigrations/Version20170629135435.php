<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170629135435 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_answer (id INT NOT NULL, answer_id INT NOT NULL, INDEX IDX_29BECE0BAA334807 (answer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_qa_answer (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, question_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, parent_entity_type SMALLINT NOT NULL, parent_entity_id INT NOT NULL, parent_entity_field VARCHAR(20) NOT NULL, body LONGTEXT NOT NULL, body_block_picture_count INT NOT NULL, body_block_video_count INT NOT NULL, comment_count INT NOT NULL, positive_vote_score INT NOT NULL, negative_vote_score INT NOT NULL, vote_score INT NOT NULL, vote_count INT NOT NULL, INDEX IDX_E69A7E56A76ED395 (user_id), INDEX IDX_E69A7E561E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_qa_answer_body_block (answer_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_2136A598AA334807 (answer_id), UNIQUE INDEX UNIQ_2136A598E9ED820C (block_id), PRIMARY KEY(answer_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_qa_question (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, body_block_picture_count INT NOT NULL, body_block_video_count INT NOT NULL, answer_count INT NOT NULL, positive_answer_count INT NOT NULL, null_answer_count INT NOT NULL, undetermined_answer_count INT NOT NULL, negative_answer_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_draft TINYINT(1) NOT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_9351AB89989D9B62 (slug), INDEX IDX_9351AB89A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_qa_question_body_block (question_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_DA8BFF891E27F6BF (question_id), UNIQUE INDEX UNIQ_DA8BFF89E9ED820C (block_id), PRIMARY KEY(question_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_qa_question_tag (question_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_C1384BA21E27F6BF (question_id), INDEX IDX_C1384BA2BAD26311 (tag_id), PRIMARY KEY(question_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_answer ADD CONSTRAINT FK_29BECE0BAA334807 FOREIGN KEY (answer_id) REFERENCES tbl_qa_answer (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_answer ADD CONSTRAINT FK_29BECE0BBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_answer ADD CONSTRAINT FK_E69A7E56A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_qa_answer ADD CONSTRAINT FK_E69A7E561E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id)');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block ADD CONSTRAINT FK_2136A598AA334807 FOREIGN KEY (answer_id) REFERENCES tbl_qa_answer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block ADD CONSTRAINT FK_2136A598E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_qa_question ADD CONSTRAINT FK_9351AB89A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block ADD CONSTRAINT FK_DA8BFF891E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block ADD CONSTRAINT FK_DA8BFF89E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_qa_question_tag ADD CONSTRAINT FK_C1384BA21E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_qa_question_tag ADD CONSTRAINT FK_C1384BA2BAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user ADD draft_question_count INT DEFAULT NULL, ADD published_question_count INT DEFAULT NULL, ADD answer_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_qa_question_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_answer DROP FOREIGN KEY FK_29BECE0BAA334807');
        $this->addSql('ALTER TABLE tbl_qa_answer_body_block DROP FOREIGN KEY FK_2136A598AA334807');
        $this->addSql('ALTER TABLE tbl_qa_answer DROP FOREIGN KEY FK_E69A7E561E27F6BF');
        $this->addSql('ALTER TABLE tbl_qa_question_body_block DROP FOREIGN KEY FK_DA8BFF891E27F6BF');
        $this->addSql('ALTER TABLE tbl_qa_question_tag DROP FOREIGN KEY FK_C1384BA21E27F6BF');
        $this->addSql('DROP TABLE tbl_core_activity_answer');
        $this->addSql('DROP TABLE tbl_qa_answer');
        $this->addSql('DROP TABLE tbl_qa_answer_body_block');
        $this->addSql('DROP TABLE tbl_qa_question');
        $this->addSql('DROP TABLE tbl_qa_question_body_block');
        $this->addSql('DROP TABLE tbl_qa_question_tag');
        $this->addSql('ALTER TABLE tbl_core_user DROP draft_question_count, DROP published_question_count, DROP answer_count');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_qa_question_count');
    }
}
