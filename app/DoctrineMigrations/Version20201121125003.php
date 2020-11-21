<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201121125003 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_publish ADD publisher_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_activity_publish ADD CONSTRAINT FK_64B1A20A6C8A061E FOREIGN KEY (publisher_user) REFERENCES tbl_core_user (id)');
        $this->addSql('CREATE INDEX IDX_64B1A20A6C8A061E ON tbl_core_activity_publish (publisher_user)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_publish DROP FOREIGN KEY FK_64B1A20A6C8A061E');
        $this->addSql('DROP INDEX IDX_64B1A20A6C8A061E ON tbl_core_activity_publish');
        $this->addSql('ALTER TABLE tbl_core_activity_publish DROP publisher_user');
    }
}
