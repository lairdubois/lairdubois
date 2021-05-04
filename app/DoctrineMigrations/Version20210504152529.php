<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210504152529 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_value_decimal (id INT NOT NULL, data NUMERIC(10, 3) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_decimal ADD CONSTRAINT FK_A9307334BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE family family VARCHAR(100) DEFAULT NULL, CHANGE power_supply power_supply INT DEFAULT NULL, CHANGE power power INT DEFAULT NULL, CHANGE weight weight NUMERIC(10, 3) DEFAULT NULL, CHANGE catalog_link catalog_link VARCHAR(255) DEFAULT NULL, CHANGE voltage voltage INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight DROP FOREIGN KEY FK_FEC14113B7585238');
        $this->addSql('DROP INDEX IDX_FEC14113B7585238 ON tbl_knowledge2_tool_value_weight');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight CHANGE integer_id decimal_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD CONSTRAINT FK_FEC1411395F0B019 FOREIGN KEY (decimal_id) REFERENCES tbl_knowledge2_value_decimal (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FEC1411395F0B019 ON tbl_knowledge2_tool_value_weight (decimal_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD PRIMARY KEY (tool_id, decimal_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight DROP FOREIGN KEY FK_FEC1411395F0B019');
        $this->addSql('DROP TABLE tbl_knowledge2_value_decimal');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool CHANGE family family LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE power_supply power_supply LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE power power LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE voltage voltage LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE weight weight LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE catalog_link catalog_link LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('DROP INDEX IDX_FEC1411395F0B019 ON tbl_knowledge2_tool_value_weight');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight CHANGE decimal_id integer_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD CONSTRAINT FK_FEC14113B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FEC14113B7585238 ON tbl_knowledge2_tool_value_weight (integer_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD PRIMARY KEY (tool_id, integer_id)');
    }
}
