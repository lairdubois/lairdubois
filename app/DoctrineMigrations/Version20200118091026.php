<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200118091026 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_howto ADD strip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD CONSTRAINT FK_65A1D8357F2DB86A FOREIGN KEY (strip_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_65A1D8357F2DB86A ON tbl_howto (strip_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_howto DROP FOREIGN KEY FK_65A1D8357F2DB86A');
        $this->addSql('DROP INDEX IDX_65A1D8357F2DB86A ON tbl_howto');
        $this->addSql('ALTER TABLE tbl_howto DROP strip_id');
    }
}
