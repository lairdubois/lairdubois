<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125133636 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD user_guide_id INT DEFAULT NULL, DROP user_guide');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63F3760F94B FOREIGN KEY (user_guide_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('CREATE INDEX IDX_364BC63F3760F94B ON tbl_knowledge2_tool (user_guide_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool DROP FOREIGN KEY FK_364BC63F3760F94B');
        $this->addSql('DROP INDEX IDX_364BC63F3760F94B ON tbl_knowledge2_tool');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD user_guide VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP user_guide_id');
    }
}
