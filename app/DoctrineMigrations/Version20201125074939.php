<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125074939 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_registration ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_registration ADD CONSTRAINT FK_7119D0ED61220EA6 FOREIGN KEY (creator_id) REFERENCES tbl_core_user (id)');
        $this->addSql('CREATE INDEX IDX_7119D0ED61220EA6 ON tbl_core_registration (creator_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_registration DROP FOREIGN KEY FK_7119D0ED61220EA6');
        $this->addSql('DROP INDEX IDX_7119D0ED61220EA6 ON tbl_core_registration');
        $this->addSql('ALTER TABLE tbl_core_registration DROP creator_id');
    }
}
