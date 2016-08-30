<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160624135230 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE `tbl_knowledge2_provider_value_products` TO `tbl_knowledge2_provider_value_products_old`;');
        $this->addSql('RENAME TABLE `tbl_knowledge2_provider_value_services` TO `tbl_knowledge2_provider_value_services_old`;');

        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_products_old` DROP FOREIGN KEY `FK_6354A66BA53A8AA`;');
        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_products_old` DROP INDEX `IDX_6354A66BA53A8AA`;');

        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_products_old` DROP FOREIGN KEY `FK_6354A66B698D3548`;');
        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_products_old` DROP INDEX `IDX_6354A66B698D3548`;');

        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_services_old` DROP FOREIGN KEY `FK_A3DC1D58A53A8AA`;');
        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_services_old` DROP INDEX `IDX_A3DC1D58A53A8AA`;');

        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_services_old` DROP FOREIGN KEY `FK_A3DC1D58698D3548`;');
        $this->addSql('ALTER TABLE `tbl_knowledge2_provider_value_services_old` DROP INDEX `IDX_A3DC1D58698D3548`;');

        $this->addSql('ALTER TABLE `tbl_knowledge2_provider` CHANGE `products` `products_old` LONGTEXT  CHARACTER SET utf8  COLLATE utf8_unicode_ci  NULL;');
        $this->addSql('ALTER TABLE `tbl_knowledge2_provider` CHANGE `services` `services_old` LONGTEXT  CHARACTER SET utf8  COLLATE utf8_unicode_ci  NULL;');

        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_products (provider_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_6354A66BA53A8AA (provider_id), INDEX IDX_6354A66BB7585238 (integer_id), PRIMARY KEY(provider_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_services (provider_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_A3DC1D58A53A8AA (provider_id), INDEX IDX_A3DC1D58B7585238 (integer_id), PRIMARY KEY(provider_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products ADD CONSTRAINT FK_6354A66BA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products ADD CONSTRAINT FK_6354A66BB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services ADD CONSTRAINT FK_A3DC1D58A53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services ADD CONSTRAINT FK_A3DC1D58B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD products LONGTEXT DEFAULT NULL, ADD services LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old ADD CONSTRAINT FK_4988347DA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old ADD CONSTRAINT FK_4988347D698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_4988347DA53A8AA ON tbl_knowledge2_provider_value_products_old (provider_id)');
        $this->addSql('CREATE INDEX IDX_4988347D698D3548 ON tbl_knowledge2_provider_value_products_old (text_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old ADD CONSTRAINT FK_A23A230A53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old ADD CONSTRAINT FK_A23A230698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A23A230A53A8AA ON tbl_knowledge2_provider_value_services_old (provider_id)');
        $this->addSql('CREATE INDEX IDX_A23A230698D3548 ON tbl_knowledge2_provider_value_services_old (text_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_products');
        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_services');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP products, DROP services');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old DROP FOREIGN KEY FK_4988347DA53A8AA');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old DROP FOREIGN KEY FK_4988347D698D3548');
        $this->addSql('DROP INDEX IDX_4988347DA53A8AA ON tbl_knowledge2_provider_value_products_old');
        $this->addSql('DROP INDEX IDX_4988347D698D3548 ON tbl_knowledge2_provider_value_products_old');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old DROP FOREIGN KEY FK_A23A230A53A8AA');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old DROP FOREIGN KEY FK_A23A230698D3548');
        $this->addSql('DROP INDEX IDX_A23A230A53A8AA ON tbl_knowledge2_provider_value_services_old');
        $this->addSql('DROP INDEX IDX_A23A230698D3548 ON tbl_knowledge2_provider_value_services_old');
    }
}
