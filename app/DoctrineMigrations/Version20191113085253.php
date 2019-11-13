<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191113085253 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_video (software_id INT NOT NULL, video_id INT NOT NULL, INDEX IDX_135A409FD7452741 (software_id), INDEX IDX_135A409F29C1004E (video_id), PRIMARY KEY(software_id, video_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_video ADD CONSTRAINT FK_135A409FD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_video ADD CONSTRAINT FK_135A409F29C1004E FOREIGN KEY (video_id) REFERENCES tbl_knowledge2_value_video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD video VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_software_value_video');
        $this->addSql('ALTER TABLE tbl_knowledge2_software DROP video');
    }
}
