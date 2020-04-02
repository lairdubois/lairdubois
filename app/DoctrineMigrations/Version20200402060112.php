<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200402060112 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD completion_100 INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD completion_100 INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD completion_100 INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD completion_100 INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD completion_100 INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP completion_100');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP completion_100');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP completion_100');
        $this->addSql('ALTER TABLE tbl_knowledge2_software DROP completion_100');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP completion_100');
    }
}
