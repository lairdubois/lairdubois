<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201123133233 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_invite (id INT NOT NULL, invitation_id INT NOT NULL, INDEX IDX_348194F9A35D7AF0 (invitation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_member (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_57D979B9296CD8AE (team_id), INDEX IDX_57D979B9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_member_invitation (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5B8B7233296CD8AE (team_id), INDEX IDX_5B8B7233F624B39D (sender_id), INDEX IDX_5B8B7233E92F8F78 (recipient_id), UNIQUE INDEX ENTITY_MEMBER_INVITATION_UNIQUE (team_id, recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_user_member (user_id INT NOT NULL, member_user_id INT NOT NULL, INDEX IDX_C859FD5DA76ED395 (user_id), INDEX IDX_C859FD5D189A6401 (member_user_id), PRIMARY KEY(user_id, member_user_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_invite ADD CONSTRAINT FK_348194F9A35D7AF0 FOREIGN KEY (invitation_id) REFERENCES tbl_core_member_invitation (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_invite ADD CONSTRAINT FK_348194F9BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_member ADD CONSTRAINT FK_57D979B9296CD8AE FOREIGN KEY (team_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member ADD CONSTRAINT FK_57D979B9A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233296CD8AE FOREIGN KEY (team_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233F624B39D FOREIGN KEY (sender_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233E92F8F78 FOREIGN KEY (recipient_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5DA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_member ADD CONSTRAINT FK_C859FD5D189A6401 FOREIGN KEY (member_user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_publish ADD publisher_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_activity_publish ADD CONSTRAINT FK_64B1A20A6C8A061E FOREIGN KEY (publisher_user) REFERENCES tbl_core_user (id)');
        $this->addSql('CREATE INDEX IDX_64B1A20A6C8A061E ON tbl_core_activity_publish (publisher_user)');
        $this->addSql('ALTER TABLE tbl_core_user ADD is_team TINYINT(1) NOT NULL, ADD team_count INT NOT NULL, ADD member_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD member_count INT DEFAULT NULL, ADD team_count INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_invite DROP FOREIGN KEY FK_348194F9A35D7AF0');
        $this->addSql('DROP TABLE tbl_core_activity_invite');
        $this->addSql('DROP TABLE tbl_core_member');
        $this->addSql('DROP TABLE tbl_core_member_invitation');
        $this->addSql('DROP TABLE tbl_core_user_member');
        $this->addSql('ALTER TABLE tbl_core_activity_publish DROP FOREIGN KEY FK_64B1A20A6C8A061E');
        $this->addSql('DROP INDEX IDX_64B1A20A6C8A061E ON tbl_core_activity_publish');
        $this->addSql('ALTER TABLE tbl_core_activity_publish DROP publisher_user');
        $this->addSql('ALTER TABLE tbl_core_user DROP is_team, DROP team_count, DROP member_count');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP member_count, DROP team_count');
    }
}
