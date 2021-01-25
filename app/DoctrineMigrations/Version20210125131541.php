<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125131541 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_user_guide (tool_id INT NOT NULL, pdf_id INT NOT NULL, INDEX IDX_3AA3F55C8F7B22CC (tool_id), INDEX IDX_3AA3F55C511FC912 (pdf_id), PRIMARY KEY(tool_id, pdf_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_pdf (id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_5EE60CE989329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_user_guide ADD CONSTRAINT FK_3AA3F55C8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_user_guide ADD CONSTRAINT FK_3AA3F55C511FC912 FOREIGN KEY (pdf_id) REFERENCES tbl_knowledge2_value_pdf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_pdf ADD CONSTRAINT FK_5EE60CE989329D25 FOREIGN KEY (resource_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_pdf ADD CONSTRAINT FK_5EE60CE9BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD user_guide VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_user_guide DROP FOREIGN KEY FK_3AA3F55C511FC912');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_user_guide');
        $this->addSql('DROP TABLE tbl_knowledge2_value_pdf');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP user_guide');
    }
}
