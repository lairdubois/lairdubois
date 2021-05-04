<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210504101630 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_english_name (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_57A867B88F7B22CC (tool_id), INDEX IDX_57A867B8698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_utilization (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_C6E992788F7B22CC (tool_id), INDEX IDX_C6E99278698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_english_name ADD CONSTRAINT FK_57A867B88F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_english_name ADD CONSTRAINT FK_57A867B8698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_utilization ADD CONSTRAINT FK_C6E992788F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_utilization ADD CONSTRAINT FK_C6E99278698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD english_name VARCHAR(255) DEFAULT NULL, ADD utilization VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_english_name');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_utilization');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP english_name, DROP utilization');
    }
}
