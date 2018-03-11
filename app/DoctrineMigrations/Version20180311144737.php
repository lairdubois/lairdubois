<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180311144737 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX IDX_COMMENT_ENTITY ON tbl_core_comment (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_JOIN_ENTITY ON tbl_core_join (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_LIKE_ENTITY ON tbl_core_like (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_REPORT_ENTITY ON tbl_core_report (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_SPOTLIGHT_ENTITY ON tbl_core_spotlight (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_VIEW_ENTITY ON tbl_core_view (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_VOTE_ENTITY ON tbl_core_vote (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_VOTE_PARENT_ENTITY ON tbl_core_vote (parent_entity_type, parent_entity_id)');
        $this->addSql('CREATE INDEX IDX_WATCH_ENTITY ON tbl_core_watch (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_WITNESS_ENTITY ON tbl_core_witness (entity_type, entity_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_COMMENT_ENTITY ON tbl_core_comment');
        $this->addSql('DROP INDEX IDX_JOIN_ENTITY ON tbl_core_join');
        $this->addSql('DROP INDEX IDX_LIKE_ENTITY ON tbl_core_like');
        $this->addSql('DROP INDEX IDX_REPORT_ENTITY ON tbl_core_report');
        $this->addSql('DROP INDEX IDX_SPOTLIGHT_ENTITY ON tbl_core_spotlight');
        $this->addSql('DROP INDEX IDX_VIEW_ENTITY ON tbl_core_view');
        $this->addSql('DROP INDEX IDX_VOTE_ENTITY ON tbl_core_vote');
        $this->addSql('DROP INDEX IDX_VOTE_PARENT_ENTITY ON tbl_core_vote');
        $this->addSql('DROP INDEX IDX_WATCH_ENTITY ON tbl_core_watch');
        $this->addSql('DROP INDEX IDX_WITNESS_ENTITY ON tbl_core_witness');
    }
}
