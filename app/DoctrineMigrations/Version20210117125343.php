<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210117125343 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_notification ADD folder_id INT DEFAULT NULL, ADD is_folder TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_notification ADD CONSTRAINT FK_ACE50180162CB942 FOREIGN KEY (folder_id) REFERENCES tbl_core_notification (id)');
        $this->addSql('CREATE INDEX IDX_ACE50180162CB942 ON tbl_core_notification (folder_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_notification DROP FOREIGN KEY FK_ACE50180162CB942');
        $this->addSql('DROP INDEX IDX_ACE50180162CB942 ON tbl_core_notification');
        $this->addSql('ALTER TABLE tbl_core_notification DROP folder_id, DROP is_folder');
    }
}
