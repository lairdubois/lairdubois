<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160624151410 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_products_old');
        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_services_old');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP products_old, DROP services_old');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_products_old (provider_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_4988347DA53A8AA (provider_id), INDEX IDX_4988347D698D3548 (text_id), PRIMARY KEY(provider_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_services_old (provider_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_A23A230A53A8AA (provider_id), INDEX IDX_A23A230698D3548 (text_id), PRIMARY KEY(provider_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old ADD CONSTRAINT FK_4988347D698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_products_old ADD CONSTRAINT FK_4988347DA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old ADD CONSTRAINT FK_A23A230698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_services_old ADD CONSTRAINT FK_A23A230A53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD products_old LONGTEXT DEFAULT NULL, ADD services_old LONGTEXT DEFAULT NULL');
    }
}
