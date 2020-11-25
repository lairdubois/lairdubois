<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125130020 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_request (id INT NOT NULL, request_id INT NOT NULL, INDEX IDX_E7B2E1D4427EB8A5 (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_request ADD CONSTRAINT FK_E7B2E1D4427EB8A5 FOREIGN KEY (request_id) REFERENCES tbl_core_member_request (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_request ADD CONSTRAINT FK_E7B2E1D4BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_activity_request');
    }
}
