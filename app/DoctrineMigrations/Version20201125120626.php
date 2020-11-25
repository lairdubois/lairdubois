<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125120626 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_member_request (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, sender_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_409468AA296CD8AE (team_id), INDEX IDX_409468AAF624B39D (sender_id), UNIQUE INDEX ENTITY_MEMBER_INVITATION_UNIQUE (team_id, sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_member_request ADD CONSTRAINT FK_409468AA296CD8AE FOREIGN KEY (team_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_request ADD CONSTRAINT FK_409468AAF624B39D FOREIGN KEY (sender_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD request_count INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_member_request');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP request_count');
    }
}
