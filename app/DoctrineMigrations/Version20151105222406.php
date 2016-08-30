<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151105222406 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge_wood_material (id INT AUTO_INCREMENT NOT NULL, wood_id INT NOT NULL, value_id INT NOT NULL, single_picture_id INT NOT NULL, mosaic_picture_id INT NOT NULL, download_count INT NOT NULL, INDEX IDX_84EC00847B2710BE (wood_id), INDEX IDX_84EC0084F920BBA2 (value_id), INDEX IDX_84EC00849E971BFA (single_picture_id), INDEX IDX_84EC008429CFA3F3 (mosaic_picture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge_wood_material ADD CONSTRAINT FK_84EC00847B2710BE FOREIGN KEY (wood_id) REFERENCES tbl_knowledge_wood (id)');
        $this->addSql('ALTER TABLE tbl_knowledge_wood_material ADD CONSTRAINT FK_84EC0084F920BBA2 FOREIGN KEY (value_id) REFERENCES tbl_knowledge_value_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge_wood_material ADD CONSTRAINT FK_84EC00849E971BFA FOREIGN KEY (single_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge_wood_material ADD CONSTRAINT FK_84EC008429CFA3F3 FOREIGN KEY (mosaic_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_find_content_event CHANGE location location VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge_wood ADD material_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge_wood_material');
        $this->addSql('ALTER TABLE tbl_find_content_event CHANGE location location VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge_wood DROP material_count');
    }
}
