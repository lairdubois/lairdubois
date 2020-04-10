<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200410123722 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_comment ADD vote_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_comment ADD CONSTRAINT FK_78309DC972DCDAFC FOREIGN KEY (vote_id) REFERENCES tbl_core_vote (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78309DC972DCDAFC ON tbl_core_comment (vote_id)');
        $this->addSql('ALTER TABLE tbl_core_vote ADD comment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_vote ADD CONSTRAINT FK_2D7D29C2F8697D13 FOREIGN KEY (comment_id) REFERENCES tbl_core_comment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2D7D29C2F8697D13 ON tbl_core_vote (comment_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_comment DROP FOREIGN KEY FK_78309DC972DCDAFC');
        $this->addSql('DROP INDEX UNIQ_78309DC972DCDAFC ON tbl_core_comment');
        $this->addSql('ALTER TABLE tbl_core_comment DROP vote_id');
        $this->addSql('ALTER TABLE tbl_core_vote DROP FOREIGN KEY FK_2D7D29C2F8697D13');
        $this->addSql('DROP INDEX UNIQ_2D7D29C2F8697D13 ON tbl_core_vote');
        $this->addSql('ALTER TABLE tbl_core_vote DROP comment_id');
    }
}
