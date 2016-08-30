<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160829132113 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_contribute ADD value_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_activity_contribute ADD CONSTRAINT FK_2C252AB2F920BBA2 FOREIGN KEY (value_id) REFERENCES tbl_knowledge2_value (id)');
        $this->addSql('CREATE INDEX IDX_2C252AB2F920BBA2 ON tbl_core_activity_contribute (value_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_contribute DROP FOREIGN KEY FK_2C252AB2F920BBA2');
        $this->addSql('DROP INDEX IDX_2C252AB2F920BBA2 ON tbl_core_activity_contribute');
        $this->addSql('ALTER TABLE tbl_core_activity_contribute DROP value_id');
    }
}
