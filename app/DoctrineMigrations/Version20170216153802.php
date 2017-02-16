<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170216153802 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_youtook_took DROP FOREIGN KEY FK_71F0E491FDFF2E92');
        $this->addSql('DROP INDEX IDX_71F0E491FDFF2E92 ON tbl_youtook_took');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD thumbnail_loc VARCHAR(255) NOT NULL, CHANGE thumbnail_id main_picture_id INT DEFAULT NULL, CHANGE description body LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD CONSTRAINT FK_71F0E491D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_71F0E491D6BDC9DC ON tbl_youtook_took (main_picture_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_youtook_took DROP FOREIGN KEY FK_71F0E491D6BDC9DC');
        $this->addSql('DROP INDEX IDX_71F0E491D6BDC9DC ON tbl_youtook_took');
        $this->addSql('ALTER TABLE tbl_youtook_took DROP thumbnail_loc, CHANGE main_picture_id thumbnail_id INT DEFAULT NULL, CHANGE body description TINYTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD CONSTRAINT FK_71F0E491FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_71F0E491FDFF2E92 ON tbl_youtook_took (thumbnail_id)');
    }
}
