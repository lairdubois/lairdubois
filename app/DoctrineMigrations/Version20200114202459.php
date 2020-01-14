<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200114202459 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E469668A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX idx_e46966834a3e1b6 TO IDX_E469668E6655814');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX entity_user_unique TO ENTITY_MENTIONED_USER_UNIQUE');
        $this->addSql('ALTER TABLE sessions CHANGE sess_time sess_time INT NOT NULL, CHANGE sess_lifetime sess_lifetime SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE tbl_offer CHANGE raw_price raw_price INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_webpush_user_subscription CHANGE subscription subscription JSON NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a82c7c2cba TO IDX_772B09CB2C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a870f2bc06 TO IDX_772B09CB70F2BC06');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sessions CHANGE sess_time sess_time INT UNSIGNED NOT NULL, CHANGE sess_lifetime sess_lifetime INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E469668A76ED395');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX idx_e469668e6655814 TO IDX_E46966834A3E1B6');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX entity_mentioned_user_unique TO ENTITY_USER_UNIQUE');
        $this->addSql('ALTER TABLE tbl_offer CHANGE raw_price raw_price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE tbl_webpush_user_subscription CHANGE subscription subscription LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb2c7c2cba TO IDX_4BCB70A82C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb70f2bc06 TO IDX_4BCB70A870F2BC06');
    }
}
