<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201122131436 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_member_invitation (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, created_at DATETIME NOT NULL, body LONGTEXT NOT NULL, htmlBody LONGTEXT NOT NULL, INDEX IDX_5B8B7233296CD8AE (team_id), INDEX IDX_5B8B7233F624B39D (sender_id), INDEX IDX_5B8B7233E92F8F78 (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233296CD8AE FOREIGN KEY (team_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233F624B39D FOREIGN KEY (sender_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_member_invitation ADD CONSTRAINT FK_5B8B7233E92F8F78 FOREIGN KEY (recipient_id) REFERENCES tbl_core_user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_member_invitation');
    }
}
