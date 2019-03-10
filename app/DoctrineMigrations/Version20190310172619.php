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
