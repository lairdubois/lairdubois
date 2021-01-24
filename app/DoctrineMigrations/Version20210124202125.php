<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210124202125 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_brand (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_A999C4C48F7B22CC (tool_id), INDEX IDX_A999C4C4698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_brand ADD CONSTRAINT FK_A999C4C48F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_brand ADD CONSTRAINT FK_A999C4C4698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD brand VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_brand');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP brand');
    }
}
