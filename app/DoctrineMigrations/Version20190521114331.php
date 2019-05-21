<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521114331 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_license_url (software_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_75A351BBD7452741 (software_id), INDEX IDX_75A351BB81CFDAE7 (url_id), PRIMARY KEY(software_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_pricings (software_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_663392FED7452741 (software_id), INDEX IDX_663392FEB7585238 (integer_id), PRIMARY KEY(software_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_supported_files (software_id INT NOT NULL, fileextension_id INT NOT NULL, INDEX IDX_85639811D7452741 (software_id), INDEX IDX_8563981174149977 (fileextension_id), PRIMARY KEY(software_id, fileextension_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_file_extension (id INT NOT NULL, data VARCHAR(10) NOT NULL, extension VARCHAR(10) NOT NULL, label VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BBD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_license_url ADD CONSTRAINT FK_75A351BB81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_pricings ADD CONSTRAINT FK_663392FED7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_pricings ADD CONSTRAINT FK_663392FEB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_supported_files ADD CONSTRAINT FK_85639811D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_supported_files ADD CONSTRAINT FK_8563981174149977 FOREIGN KEY (fileextension_id) REFERENCES tbl_knowledge2_value_file_extension (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_file_extension ADD CONSTRAINT FK_70AE476BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_licenses');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD pricings VARCHAR(255) DEFAULT NULL, ADD supported_files LONGTEXT DEFAULT NULL, CHANGE licenses license_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_supported_files DROP FOREIGN KEY FK_8563981174149977');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_licenses (software_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_BD3AC270D7452741 (software_id), INDEX IDX_BD3AC270B7585238 (integer_id), PRIMARY KEY(software_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_licenses ADD CONSTRAINT FK_BD3AC270B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_licenses ADD CONSTRAINT FK_BD3AC270D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_license_url');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_pricings');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_supported_files');
        $this->addSql('DROP TABLE tbl_knowledge2_value_file_extension');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD licenses VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP license_url, DROP pricings, DROP supported_files');
    }
}
