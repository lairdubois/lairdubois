<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170703075321 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_qa_answer ADD is_best_answer TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD best_answer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD CONSTRAINT FK_9351AB89B6DEA817 FOREIGN KEY (best_answer_id) REFERENCES tbl_qa_answer (id)');
        $this->addSql('CREATE INDEX IDX_9351AB89B6DEA817 ON tbl_qa_question (best_answer_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_qa_answer DROP is_best_answer');
        $this->addSql('ALTER TABLE tbl_qa_question DROP FOREIGN KEY FK_9351AB89B6DEA817');
        $this->addSql('DROP INDEX IDX_9351AB89B6DEA817 ON tbl_qa_question');
        $this->addSql('ALTER TABLE tbl_qa_question DROP best_answer_id');
    }
}
