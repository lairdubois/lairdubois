<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170703133619 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_qa_question ADD main_picture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD CONSTRAINT FK_9351AB89D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_9351AB89D6BDC9DC ON tbl_qa_question (main_picture_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_qa_question DROP FOREIGN KEY FK_9351AB89D6BDC9DC');
        $this->addSql('DROP INDEX IDX_9351AB89D6BDC9DC ON tbl_qa_question');
        $this->addSql('ALTER TABLE tbl_qa_question DROP main_picture_id');
    }
}
