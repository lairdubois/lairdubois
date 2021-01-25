<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125163011 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_manual (tool_id INT NOT NULL, pdf_id INT NOT NULL, INDEX IDX_E9D7AA968F7B22CC (tool_id), INDEX IDX_E9D7AA96511FC912 (pdf_id), PRIMARY KEY(tool_id, pdf_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual ADD CONSTRAINT FK_E9D7AA968F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual ADD CONSTRAINT FK_E9D7AA96511FC912 FOREIGN KEY (pdf_id) REFERENCES tbl_knowledge2_value_pdf (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_user_guide');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP FOREIGN KEY FK_364BC63F3760F94B');
        $this->addSql('DROP INDEX IDX_364BC63F3760F94B ON tbl_knowledge2_tool');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE user_guide_id manual_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63F9BA073D6 FOREIGN KEY (manual_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('CREATE INDEX IDX_364BC63F9BA073D6 ON tbl_knowledge2_tool (manual_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_user_guide (tool_id INT NOT NULL, pdf_id INT NOT NULL, INDEX IDX_3AA3F55C511FC912 (pdf_id), INDEX IDX_3AA3F55C8F7B22CC (tool_id), PRIMARY KEY(tool_id, pdf_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_user_guide ADD CONSTRAINT FK_3AA3F55C511FC912 FOREIGN KEY (pdf_id) REFERENCES tbl_knowledge2_value_pdf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_user_guide ADD CONSTRAINT FK_3AA3F55C8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_manual');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP FOREIGN KEY FK_364BC63F9BA073D6');
        $this->addSql('DROP INDEX IDX_364BC63F9BA073D6 ON tbl_knowledge2_tool');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE manual_id user_guide_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63F3760F94B FOREIGN KEY (user_guide_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('CREATE INDEX IDX_364BC63F3760F94B ON tbl_knowledge2_tool (user_guide_id)');
    }
}
