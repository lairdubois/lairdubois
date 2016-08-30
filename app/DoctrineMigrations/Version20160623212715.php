<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160623212715 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_witness (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, kind SMALLINT NOT NULL, meta LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_blog_post DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_faq_question DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_find DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_howto_article DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_howto DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP state_code, DROP state_data');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP state_code, DROP state_data');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_witness');
        $this->addSql('ALTER TABLE tbl_blog_post ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_faq_question ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_find ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_howto ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_howto_article ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD state_code SMALLINT NOT NULL, ADD state_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
