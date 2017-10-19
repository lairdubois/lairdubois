<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170810212032 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_comment ADD parent_id INT DEFAULT NULL, ADD childCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_comment ADD CONSTRAINT FK_78309DC9727ACA70 FOREIGN KEY (parent_id) REFERENCES tbl_core_comment (id)');
        $this->addSql('CREATE INDEX IDX_78309DC9727ACA70 ON tbl_core_comment (parent_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_comment DROP FOREIGN KEY FK_78309DC9727ACA70');
        $this->addSql('DROP INDEX IDX_78309DC9727ACA70 ON tbl_core_comment');
        $this->addSql('ALTER TABLE tbl_core_comment DROP parent_id, DROP childCount');
    }
}
