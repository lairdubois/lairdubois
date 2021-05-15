<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210515091743 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book CHANGE catalogLink catalogLink LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider CHANGE website website VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school CHANGE website website VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_software CHANGE website website VARCHAR(2048) DEFAULT NULL, CHANGE source_core_repository source_core_repository VARCHAR(2048) DEFAULT NULL, CHANGE source_docs source_docs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE catalog_link catalog_link LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_value CHANGE source source VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_url CHANGE data data VARCHAR(2048) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book CHANGE catalogLink catalogLink VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider CHANGE website website VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_school CHANGE website website VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_software CHANGE website website VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE source_core_repository source_core_repository VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE source_docs source_docs VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE catalog_link catalog_link VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_value CHANGE source source VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_url CHANGE data data VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
