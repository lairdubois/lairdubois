<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191218082105 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_wonder_plan_question (plan_id INT NOT NULL, question_id INT NOT NULL, INDEX IDX_191ACBE899029B (plan_id), INDEX IDX_191ACB1E27F6BF (question_id), PRIMARY KEY(plan_id, question_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_plan_school (plan_id INT NOT NULL, school_id INT NOT NULL, INDEX IDX_8F7C0363E899029B (plan_id), INDEX IDX_8F7C0363C32A47EE (school_id), PRIMARY KEY(plan_id, school_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_wonder_plan_question ADD CONSTRAINT FK_191ACBE899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_plan_question ADD CONSTRAINT FK_191ACB1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_plan_school ADD CONSTRAINT FK_8F7C0363E899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_plan_school ADD CONSTRAINT FK_8F7C0363C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD plan_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD plan_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD question_count INT NOT NULL, ADD school_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_wonder_plan_question');
        $this->addSql('DROP TABLE tbl_wonder_plan_school');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP plan_count');
        $this->addSql('ALTER TABLE tbl_qa_question DROP plan_count');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP question_count, DROP school_count');
    }
}
