<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201115153850 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_member (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_57D979B9296CD8AE (team_id), INDEX IDX_57D979B9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_user_member (user_id INT NOT NULL, member_user_id INT NOT NULL, INDEX IDX_C859FD5DA76ED395 (user_id), INDEX IDX_C859FD5D189A6401 (member_user_id), PRIMARY KEY(user_id, member_user_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_member ADD CONSTRAINT FK_57D979B9296CD8AE FOREIGN KEY (team_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member ADD CONSTRAINT FK_57D979B9A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5DA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5D189A6401 FOREIGN KEY (member_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD team_count INT NOT NULL, ADD member_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD member_count INT DEFAULT NULL, ADD team_count INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_member');
        $this->addSql('DROP TABLE tbl_core_user_member');
        $this->addSql('ALTER TABLE tbl_core_user DROP team_count, DROP member_count');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP member_count, DROP team_count');
    }
}
