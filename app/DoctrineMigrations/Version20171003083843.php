<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171003083843 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_promotion_graphic (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, resource_id INT NOT NULL, license_id INT DEFAULT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, htmlBody LONGTEXT NOT NULL, zip_archive_size INT NOT NULL, download_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_draft TINYINT(1) NOT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_9AC6BD82989D9B62 (slug), INDEX IDX_9AC6BD82D6BDC9DC (main_picture_id), INDEX IDX_9AC6BD8289329D25 (resource_id), UNIQUE INDEX UNIQ_9AC6BD82460F904B (license_id), INDEX IDX_9AC6BD82A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_promotion_graphic_tag (graphic_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_494EB7183EDB30E6 (graphic_id), INDEX IDX_494EB718BAD26311 (tag_id), PRIMARY KEY(graphic_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD CONSTRAINT FK_9AC6BD82D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD CONSTRAINT FK_9AC6BD8289329D25 FOREIGN KEY (resource_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD CONSTRAINT FK_9AC6BD82460F904B FOREIGN KEY (license_id) REFERENCES tbl_core_license (id)');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD CONSTRAINT FK_9AC6BD82A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_promotion_graphic_tag ADD CONSTRAINT FK_494EB7183EDB30E6 FOREIGN KEY (graphic_id) REFERENCES tbl_promotion_graphic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_promotion_graphic_tag ADD CONSTRAINT FK_494EB718BAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_resource ADD thumbnail_id INT DEFAULT NULL, ADD kind INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_resource ADD CONSTRAINT FK_1AC1E7BEFDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_1AC1E7BEFDFF2E92 ON tbl_core_resource (thumbnail_id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD draft_graphic_count INT DEFAULT NULL, ADD published_graphic_count INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_promotion_graphic_tag DROP FOREIGN KEY FK_494EB7183EDB30E6');
        $this->addSql('DROP TABLE tbl_promotion_graphic');
        $this->addSql('DROP TABLE tbl_promotion_graphic_tag');
        $this->addSql('ALTER TABLE tbl_core_resource DROP FOREIGN KEY FK_1AC1E7BEFDFF2E92');
        $this->addSql('DROP INDEX IDX_1AC1E7BEFDFF2E92 ON tbl_core_resource');
        $this->addSql('ALTER TABLE tbl_core_resource DROP thumbnail_id, DROP kind');
        $this->addSql('ALTER TABLE tbl_core_user DROP draft_graphic_count, DROP published_graphic_count');
    }
}
