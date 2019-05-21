<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521163523 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_license_type (software_id INT NOT NULL, linkabletext_id INT NOT NULL, INDEX IDX_E18266D6D7452741 (software_id), INDEX IDX_E18266D6CDB32A8D (linkabletext_id), PRIMARY KEY(software_id, linkabletext_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_type ADD CONSTRAINT FK_E18266D6D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_type ADD CONSTRAINT FK_E18266D6CDB32A8D FOREIGN KEY (linkabletext_id) REFERENCES tbl_knowledge2_value_linkable_text (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_license_url');
        $this->addSql('ALTER TABLE tbl_knowledge2_software CHANGE license_url license_type VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_license_url (software_id INT NOT NULL, linkabletext_id INT NOT NULL, INDEX IDX_75A351BBD7452741 (software_id), INDEX IDX_75A351BBCDB32A8D (linkabletext_id), PRIMARY KEY(software_id, linkabletext_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BBCDB32A8D FOREIGN KEY (linkabletext_id) REFERENCES tbl_knowledge2_value_linkable_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BBD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_license_type');
        $this->addSql('ALTER TABLE tbl_knowledge2_software CHANGE license_type license_url VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
