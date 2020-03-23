<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323111727 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_feedback_body_block (feedback_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_E5A8F32BD249A887 (feedback_id), UNIQUE INDEX UNIQ_E5A8F32BE9ED820C (block_id), PRIMARY KEY(feedback_id, block_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_feedback_body_block ADD CONSTRAINT FK_E5A8F32BD249A887 FOREIGN KEY (feedback_id) REFERENCES tbl_core_feedback (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_feedback_body_block ADD CONSTRAINT FK_E5A8F32BE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_feedback ADD bodyExtract VARCHAR(255) NOT NULL, ADD body_block_picture_count INT NOT NULL, ADD body_block_video_count INT NOT NULL, DROP html_body');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_feedback_body_block');
        $this->addSql('ALTER TABLE tbl_core_feedback ADD html_body LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP bodyExtract, DROP body_block_picture_count, DROP body_block_video_count');
    }
}
