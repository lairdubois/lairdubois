<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170215172411 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_youtook_took (id INT AUTO_INCREMENT NOT NULL, thumbnail_id INT DEFAULT NULL, user_id INT NOT NULL, slug VARCHAR(100) NOT NULL, url VARCHAR(255) NOT NULL, kind SMALLINT NOT NULL, embedIdentifier VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TINYTEXT NOT NULL, channel_id VARCHAR(255) NOT NULL, channel_thumbnail_loc VARCHAR(255) NOT NULL, channel_title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_draft TINYINT(1) NOT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_71F0E491989D9B62 (slug), INDEX IDX_71F0E491FDFF2E92 (thumbnail_id), INDEX IDX_71F0E491A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD CONSTRAINT FK_71F0E491FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD CONSTRAINT FK_71F0E491A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('DROP TABLE tbl_youtube_video');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_youtube_video (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, embedIdentifier VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, thumbnail_loc VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, title VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, description TINYTEXT NOT NULL COLLATE utf8_unicode_ci, channel_id VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, channel_thumbnail_loc VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, channel_title VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, channel_description LONGTEXT NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_B7DFA8C2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_youtube_video ADD CONSTRAINT FK_B7DFA8C2A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('DROP TABLE tbl_youtook_took');
    }
}
