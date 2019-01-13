<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190113160908 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX ENTITY_USER_UNIQUE ON tbl_core_join (entity_type, entity_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX ENTITY_USER_UNIQUE ON tbl_core_like (entity_type, entity_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX ENTITY_USER_UNIQUE ON tbl_core_vote (entity_type, entity_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX ENTITY_USER_UNIQUE ON tbl_core_watch (entity_type, entity_id, user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX ENTITY_USER_UNIQUE ON tbl_core_join');
        $this->addSql('DROP INDEX ENTITY_USER_UNIQUE ON tbl_core_like');
        $this->addSql('DROP INDEX ENTITY_USER_UNIQUE ON tbl_core_vote');
        $this->addSql('DROP INDEX ENTITY_USER_UNIQUE ON tbl_core_watch');
    }
}
