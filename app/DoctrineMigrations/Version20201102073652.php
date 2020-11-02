<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201102073652 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_provider_value_video (provider_id INT NOT NULL, video_id INT NOT NULL, INDEX IDX_8B8C6EDAA53A8AA (provider_id), INDEX IDX_8B8C6EDA29C1004E (video_id), PRIMARY KEY(provider_id, video_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_video ADD CONSTRAINT FK_8B8C6EDAA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider_value_video ADD CONSTRAINT FK_8B8C6EDA29C1004E FOREIGN KEY (video_id) REFERENCES tbl_knowledge2_value_video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD video VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_provider_value_video');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP video');
    }
}
