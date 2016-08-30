<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130728123838 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_question_answer DROP FOREIGN KEY FK_3AC30A31E27F6BF");
        $this->addSql("ALTER TABLE tbl_question_tag DROP FOREIGN KEY FK_CDCE2C6D1E27F6BF");
        $this->addSql("ALTER TABLE tbl_question_vote DROP FOREIGN KEY FK_1D6C0D801E27F6BF");
        $this->addSql("ALTER TABLE tbl_question_answer_vote DROP FOREIGN KEY FK_3BD61F5FAA334807");
        $this->addSql("DROP TABLE tbl_question");
        $this->addSql("DROP TABLE tbl_question_answer");
        $this->addSql("DROP TABLE tbl_question_answer_vote");
        $this->addSql("DROP TABLE tbl_question_tag");
        $this->addSql("DROP TABLE tbl_question_vote");
        $this->addSql("ALTER TABLE tbl_core_user DROP published_question_count, DROP published_answer_count");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_question (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, answer_count INT NOT NULL, promoted_answer_id INT NOT NULL, vote_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, UNIQUE INDEX UNIQ_E1C4AF63989D9B62 (slug), INDEX IDX_E1C4AF63A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_question_answer (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, body LONGTEXT NOT NULL, promoted TINYINT(1) NOT NULL, vote_count INT NOT NULL, comment_count INT NOT NULL, INDEX IDX_3AC30A3A76ED395 (user_id), INDEX IDX_3AC30A31E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_question_answer_vote (user_id INT NOT NULL, answer_id INT NOT NULL, created_at DATETIME NOT NULL, up TINYINT(1) NOT NULL, INDEX IDX_3BD61F5FA76ED395 (user_id), INDEX IDX_3BD61F5FAA334807 (answer_id), PRIMARY KEY(user_id, answer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_question_tag (question_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_CDCE2C6D1E27F6BF (question_id), INDEX IDX_CDCE2C6DBAD26311 (tag_id), PRIMARY KEY(question_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_question_vote (user_id INT NOT NULL, question_id INT NOT NULL, created_at DATETIME NOT NULL, up TINYINT(1) NOT NULL, INDEX IDX_1D6C0D80A76ED395 (user_id), INDEX IDX_1D6C0D801E27F6BF (question_id), PRIMARY KEY(user_id, question_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_question ADD CONSTRAINT FK_E1C4AF63A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("ALTER TABLE tbl_question_answer ADD CONSTRAINT FK_3AC30A31E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_question (id)");
        $this->addSql("ALTER TABLE tbl_question_answer ADD CONSTRAINT FK_3AC30A3A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("ALTER TABLE tbl_question_answer_vote ADD CONSTRAINT FK_3BD61F5FA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("ALTER TABLE tbl_question_answer_vote ADD CONSTRAINT FK_3BD61F5FAA334807 FOREIGN KEY (answer_id) REFERENCES tbl_question_answer (id)");
        $this->addSql("ALTER TABLE tbl_question_tag ADD CONSTRAINT FK_CDCE2C6D1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_question (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_question_tag ADD CONSTRAINT FK_CDCE2C6DBAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_question_vote ADD CONSTRAINT FK_1D6C0D801E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_question (id)");
        $this->addSql("ALTER TABLE tbl_question_vote ADD CONSTRAINT FK_1D6C0D80A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("ALTER TABLE tbl_core_user ADD published_question_count INT DEFAULT NULL, ADD published_answer_count INT DEFAULT NULL");
    }
}
