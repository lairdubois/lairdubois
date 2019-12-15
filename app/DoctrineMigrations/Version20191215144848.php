<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191215144848 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_wonder_creation_question (creation_id INT NOT NULL, question_id INT NOT NULL, INDEX IDX_E0A1EF7F34FFA69A (creation_id), INDEX IDX_E0A1EF7F1E27F6BF (question_id), PRIMARY KEY(creation_id, question_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_creation_school (creation_id INT NOT NULL, school_id INT NOT NULL, INDEX IDX_4E32E27634FFA69A (creation_id), INDEX IDX_4E32E276C32A47EE (school_id), PRIMARY KEY(creation_id, school_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_wonder_creation_question ADD CONSTRAINT FK_E0A1EF7F34FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_question ADD CONSTRAINT FK_E0A1EF7F1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_qa_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_school ADD CONSTRAINT FK_4E32E27634FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_school ADD CONSTRAINT FK_4E32E276C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD question_count INT NOT NULL, ADD school_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_wonder_creation_question');
        $this->addSql('DROP TABLE tbl_wonder_creation_school');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP question_count, DROP school_count');
    }
}
