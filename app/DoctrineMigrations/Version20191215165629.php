<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191215165629 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_wonder_howto_question (howto_id INT NOT NULL, question_id INT NOT NULL, INDEX IDX_2AFD8D2DFBE2D86A (howto_id), INDEX IDX_2AFD8D2D1E27F6BF (question_id), PRIMARY KEY(howto_id, question_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_howto_school (howto_id INT NOT NULL, school_id INT NOT NULL, INDEX IDX_45249898FBE2D86A (howto_id), INDEX IDX_45249898C32A47EE (school_id), PRIMARY KEY(howto_id, school_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_wonder_howto_question ADD CONSTRAINT FK_2AFD8D2DFBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_howto_question ADD CONSTRAINT FK_2AFD8D2D1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_school ADD CONSTRAINT FK_45249898FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_school ADD CONSTRAINT FK_45249898C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto ADD question_count INT NOT NULL, ADD school_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD howto_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_wonder_howto_question');
        $this->addSql('DROP TABLE tbl_howto_school');
        $this->addSql('ALTER TABLE tbl_howto DROP question_count, DROP school_count');
        $this->addSql('ALTER TABLE tbl_qa_question DROP howto_count');
    }
}
