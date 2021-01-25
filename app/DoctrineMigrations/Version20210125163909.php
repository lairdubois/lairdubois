<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125163909 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family DROP FOREIGN KEY FK_5CEA3509698D3548');
        $this->addSql('DROP INDEX IDX_5CEA3509698D3548 ON tbl_knowledge2_tool_value_family');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family CHANGE text_id integer_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD CONSTRAINT FK_5CEA3509B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5CEA3509B7585238 ON tbl_knowledge2_tool_value_family (integer_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD PRIMARY KEY (tool_id, integer_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family DROP FOREIGN KEY FK_5CEA3509B7585238');
        $this->addSql('DROP INDEX IDX_5CEA3509B7585238 ON tbl_knowledge2_tool_value_family');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family CHANGE integer_id text_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD CONSTRAINT FK_5CEA3509698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5CEA3509698D3548 ON tbl_knowledge2_tool_value_family (text_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD PRIMARY KEY (tool_id, text_id)');
    }
}
