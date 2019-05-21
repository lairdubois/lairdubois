<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521161543 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_value_linkable_text (id INT NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_linkable_text ADD CONSTRAINT FK_B6BFA940BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url DROP FOREIGN KEY FK_75A351BB81CFDAE7');
        $this->addSql('DROP INDEX IDX_75A351BB81CFDAE7 ON tbl_knowledge2_software_value_license_url');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url CHANGE url_id linkabletext_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BBCDB32A8D FOREIGN KEY (linkabletext_id) REFERENCES tbl_knowledge2_value_linkable_text (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_75A351BBCDB32A8D ON tbl_knowledge2_software_value_license_url (linkabletext_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD PRIMARY KEY (software_id, linkabletext_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url DROP FOREIGN KEY FK_75A351BBCDB32A8D');
        $this->addSql('DROP TABLE tbl_knowledge2_value_linkable_text');
        $this->addSql('DROP INDEX IDX_75A351BBCDB32A8D ON tbl_knowledge2_software_value_license_url');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url CHANGE linkabletext_id url_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BB81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_75A351BB81CFDAE7 ON tbl_knowledge2_software_value_license_url (url_id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD PRIMARY KEY (software_id, url_id)');
    }
}
