<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160606153705 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_description (provider_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_B7F6AE9FA53A8AA (provider_id), INDEX IDX_B7F6AE9FABCBF34C (longtext_id), PRIMARY KEY(provider_id, longtext_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_longtext (id INT NOT NULL, data LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_description ADD CONSTRAINT FK_B7F6AE9FA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_description ADD CONSTRAINT FK_B7F6AE9FABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_longtext ADD CONSTRAINT FK_D4FA10B6BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD description LONGTEXT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_description DROP FOREIGN KEY FK_B7F6AE9FABCBF34C');
        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_description');
        $this->addSql('DROP TABLE tbl_knowledge2_value_longtext');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP description');
    }
}
