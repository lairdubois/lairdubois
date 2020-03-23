<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323183209 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_feedback (id INT NOT NULL, feedback_id INT NOT NULL, INDEX IDX_33FBF92ED249A887 (feedback_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_feedback (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, bodyExtract VARCHAR(255) NOT NULL, body_block_picture_count INT NOT NULL, body_block_video_count INT NOT NULL, INDEX IDX_747957F0A76ED395 (user_id), INDEX IDX_FEEDBACK_ENTITY (entity_type, entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_feedback_body_block (feedback_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_E5A8F32BD249A887 (feedback_id), UNIQUE INDEX UNIQ_E5A8F32BE9ED820C (block_id), PRIMARY KEY(feedback_id, block_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_feedback ADD CONSTRAINT FK_33FBF92ED249A887 FOREIGN KEY (feedback_id) REFERENCES tbl_core_feedback (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_feedback ADD CONSTRAINT FK_33FBF92EBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_feedback ADD CONSTRAINT FK_747957F0A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_feedback_body_block ADD CONSTRAINT FK_E5A8F32BD249A887 FOREIGN KEY (feedback_id) REFERENCES tbl_core_feedback (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_feedback_body_block ADD CONSTRAINT FK_E5A8F32BE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E46966834A3E1B6');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E469668A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('DROP INDEX idx_e46966834a3e1b6 ON tbl_core_mention');
        $this->addSql('CREATE INDEX IDX_E469668E6655814 ON tbl_core_mention (mentioned_user_id)');
        $this->addSql('DROP INDEX entity_user_unique ON tbl_core_mention');
        $this->addSql('CREATE UNIQUE INDEX ENTITY_MENTIONED_USER_UNIQUE ON tbl_core_mention (entity_type, entity_id, mentioned_user_id)');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E46966834A3E1B6 FOREIGN KEY (mentioned_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD feedback_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_event ADD feedback_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD feedback_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_4BCB70A82C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_4BCB70A870F2BC06');
        $this->addSql('DROP INDEX idx_4bcb70a82c7c2cba ON tbl_workflow_inspiration');
        $this->addSql('CREATE INDEX IDX_772B09CB2C7C2CBA ON tbl_workflow_inspiration (workflow_id)');
        $this->addSql('DROP INDEX idx_4bcb70a870f2bc06 ON tbl_workflow_inspiration');
        $this->addSql('CREATE INDEX IDX_772B09CB70F2BC06 ON tbl_workflow_inspiration (rebound_workflow_id)');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_4BCB70A82C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_4BCB70A870F2BC06 FOREIGN KEY (rebound_workflow_id) REFERENCES tbl_workflow (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_feedback DROP FOREIGN KEY FK_33FBF92ED249A887');
        $this->addSql('ALTER TABLE tbl_core_feedback_body_block DROP FOREIGN KEY FK_E5A8F32BD249A887');
        $this->addSql('DROP TABLE tbl_core_activity_feedback');
        $this->addSql('DROP TABLE tbl_core_feedback');
        $this->addSql('DROP TABLE tbl_core_feedback_body_block');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E469668A76ED395');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E469668E6655814');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX entity_mentioned_user_unique ON tbl_core_mention');
        $this->addSql('CREATE UNIQUE INDEX ENTITY_USER_UNIQUE ON tbl_core_mention (entity_type, entity_id, mentioned_user_id)');
        $this->addSql('DROP INDEX idx_e469668e6655814 ON tbl_core_mention');
        $this->addSql('CREATE INDEX IDX_E46966834A3E1B6 ON tbl_core_mention (mentioned_user_id)');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E469668E6655814 FOREIGN KEY (mentioned_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP feedback_count');
        $this->addSql('ALTER TABLE tbl_event DROP feedback_count');
        $this->addSql('ALTER TABLE tbl_webpush_user_subscription CHANGE subscription subscription LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP feedback_count');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_772B09CB2C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_772B09CB70F2BC06');
        $this->addSql('DROP INDEX idx_772b09cb70f2bc06 ON tbl_workflow_inspiration');
        $this->addSql('CREATE INDEX IDX_4BCB70A870F2BC06 ON tbl_workflow_inspiration (rebound_workflow_id)');
        $this->addSql('DROP INDEX idx_772b09cb2c7c2cba ON tbl_workflow_inspiration');
        $this->addSql('CREATE INDEX IDX_4BCB70A82C7C2CBA ON tbl_workflow_inspiration (workflow_id)');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_772B09CB2C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_772B09CB70F2BC06 FOREIGN KEY (rebound_workflow_id) REFERENCES tbl_workflow (id)');
    }
}
