<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190310172619 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_collection (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT DEFAULT NULL, html_body LONGTEXT DEFAULT NULL, entry_count INT NOT NULL, entry_type_counters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, visibility INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_950E2FA3989D9B62 (slug), INDEX IDX_950E2FA3D6BDC9DC (main_picture_id), INDEX IDX_950E2FA3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_collection_tag (collection_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_3CCB530A514956FD (collection_id), INDEX IDX_3CCB530ABAD26311 (tag_id), PRIMARY KEY(collection_id, tag_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_collection_entry (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, sort_index INT NOT NULL, INDEX IDX_6607A751514956FD (collection_id), INDEX IDX_COLLECTION_ENTRY_ENTITY (entity_type, entity_id), UNIQUE INDEX ENTITY_COLLECTION_UNIQUE (entity_type, entity_id, collection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_collection ADD CONSTRAINT FK_950E2FA3D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_collection ADD CONSTRAINT FK_950E2FA3A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_collection_tag ADD CONSTRAINT FK_3CCB530A514956FD FOREIGN KEY (collection_id) REFERENCES tbl_collection (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_collection_tag ADD CONSTRAINT FK_3CCB530ABAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_collection_entry ADD CONSTRAINT FK_6607A751514956FD FOREIGN KEY (collection_id) REFERENCES tbl_collection (id)');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_collection_collection_count INT NOT NULL, ADD private_collection_count INT DEFAULT NULL, ADD public_collection_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_find ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow ADD private_collection_count INT NOT NULL, ADD public_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a82c7c2cba TO IDX_772B09CB2C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a870f2bc06 TO IDX_772B09CB70F2BC06');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_collection_tag DROP FOREIGN KEY FK_3CCB530A514956FD');
        $this->addSql('ALTER TABLE tbl_collection_entry DROP FOREIGN KEY FK_6607A751514956FD');
        $this->addSql('DROP TABLE tbl_collection');
        $this->addSql('DROP TABLE tbl_collection_tag');
        $this->addSql('DROP TABLE tbl_collection_entry');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E469668A76ED395');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX idx_e469668e6655814 TO IDX_E46966834A3E1B6');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX entity_mentioned_user_unique TO ENTITY_USER_UNIQUE');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_collection_collection_count, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_find DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_howto DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_promotion_graphic DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_qa_question DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_workflow DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb2c7c2cba TO IDX_4BCB70A82C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb70f2bc06 TO IDX_4BCB70A870F2BC06');
    }
}
