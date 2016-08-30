<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160601211625 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD is_affiliate TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_sign ADD is_affiliate TINYINT(1) NOT NULL');
        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_woods (provider_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_92C89A59A53A8AA (provider_id), INDEX IDX_92C89A59698D3548 (text_id), PRIMARY KEY(provider_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_woods ADD CONSTRAINT FK_92C89A59A53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_woods ADD CONSTRAINT FK_92C89A59698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD woods LONGTEXT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP is_affiliate');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_sign DROP is_affiliate');
        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_woods');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP woods');
    }
}
