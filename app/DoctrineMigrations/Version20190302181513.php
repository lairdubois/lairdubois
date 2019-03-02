<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190302181513 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_mention (id INT AUTO_INCREMENT NOT NULL, mentioned_user_id INT DEFAULT NULL, user_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, INDEX IDX_E46966834A3E1B6 (mentioned_user_id), INDEX IDX_E469668A76ED395 (user_id), INDEX IDX_MENTION_ENTITY (entity_type, entity_id), UNIQUE INDEX ENTITY_USER_UNIQUE (entity_type, entity_id, mentioned_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E46966834A3E1B6 FOREIGN KEY (mentioned_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E469668A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_mention ADD mention_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_activity_mention ADD CONSTRAINT FK_3E2737867A4147F0 FOREIGN KEY (mention_id) REFERENCES tbl_core_mention (id)');
        $this->addSql('CREATE INDEX IDX_3E2737867A4147F0 ON tbl_core_activity_mention (mention_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_mention DROP FOREIGN KEY FK_3E2737867A4147F0');
        $this->addSql('DROP TABLE tbl_core_mention');
        $this->addSql('DROP INDEX IDX_3E2737867A4147F0 ON tbl_core_activity_mention');
        $this->addSql('ALTER TABLE tbl_core_activity_mention DROP mention_id');
    }
}
