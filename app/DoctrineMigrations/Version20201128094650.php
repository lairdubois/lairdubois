<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201128094650 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_user_member');
        $this->addSql('ALTER TABLE tbl_core_user DROP team_count, DROP member_count');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_user_member (user_id INT NOT NULL, member_user_id INT NOT NULL, INDEX IDX_C859FD5D189A6401 (member_user_id), INDEX IDX_C859FD5DA76ED395 (user_id), PRIMARY KEY(user_id, member_user_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5D189A6401 FOREIGN KEY (member_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5DA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD team_count INT NOT NULL, ADD member_count INT NOT NULL');
    }
}
