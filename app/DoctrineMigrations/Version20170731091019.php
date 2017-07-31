<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170731091019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_find_body_block (find_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_BCCF64F651B74D69 (find_id), UNIQUE INDEX UNIQ_BCCF64F6E9ED820C (block_id), PRIMARY KEY(find_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_find_body_block ADD CONSTRAINT FK_BCCF64F651B74D69 FOREIGN KEY (find_id) REFERENCES tbl_find (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_find_body_block ADD CONSTRAINT FK_BCCF64F6E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)');
        $this->addSql('ALTER TABLE tbl_find ADD body_block_picture_count INT NOT NULL, ADD body_block_video_count INT NOT NULL');
		$this->addSql('ALTER TABLE tbl_find DROP htmlBody');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_find_body_block');
        $this->addSql('ALTER TABLE tbl_find DROP body_block_picture_count, DROP body_block_video_count');
		$this->addSql('ALTER TABLE tbl_find ADD htmlBody LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
    }
}
