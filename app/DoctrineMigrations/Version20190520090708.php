<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190520090708 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_software (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, screenshot_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, contributor_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, private_collection_count INT NOT NULL, public_collection_count INT NOT NULL, view_count INT NOT NULL, name VARCHAR(100) DEFAULT NULL, is_addon TINYINT(1) NOT NULL, host_software_name VARCHAR(100) DEFAULT NULL, identity VARCHAR(100) DEFAULT NULL, name_rejected TINYINT(1) NOT NULL, icon_rejected TINYINT(1) NOT NULL, authors VARCHAR(255) DEFAULT NULL, last_version VARCHAR(255) DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, open_source TINYINT(1) DEFAULT NULL, source_core_repository VARCHAR(255) DEFAULT NULL, operating_systems LONGTEXT DEFAULT NULL, licenses VARCHAR(255) DEFAULT NULL, features LONGTEXT DEFAULT NULL, languages LONGTEXT DEFAULT NULL, review_count INT NOT NULL, average_rating DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_226FED4D989D9B62 (slug), INDEX IDX_226FED4DD6BDC9DC (main_picture_id), INDEX IDX_226FED4D4A8B36A0 (screenshot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_identity (software_id INT NOT NULL, softwareidentity_id INT NOT NULL, INDEX IDX_A89D248BD7452741 (software_id), INDEX IDX_A89D248BBDCD912A (softwareidentity_id), PRIMARY KEY(software_id, softwareidentity_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_icon (software_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_48797B0DD7452741 (software_id), INDEX IDX_48797B0DEE45BDBF (picture_id), PRIMARY KEY(software_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_screenshot (software_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_CAAD318ED7452741 (software_id), INDEX IDX_CAAD318EEE45BDBF (picture_id), PRIMARY KEY(software_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_authors (software_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_513EC2EDD7452741 (software_id), INDEX IDX_513EC2ED698D3548 (text_id), PRIMARY KEY(software_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_last_version (software_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_3E238B67D7452741 (software_id), INDEX IDX_3E238B67698D3548 (text_id), PRIMARY KEY(software_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_publisher (software_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_7A49818AD7452741 (software_id), INDEX IDX_7A49818A698D3548 (text_id), PRIMARY KEY(software_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_website (software_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_985DB55BD7452741 (software_id), INDEX IDX_985DB55B81CFDAE7 (url_id), PRIMARY KEY(software_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_description (software_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_66ADAB28D7452741 (software_id), INDEX IDX_66ADAB28ABCBF34C (longtext_id), PRIMARY KEY(software_id, longtext_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_open_source (software_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_BDEB8C68D7452741 (software_id), INDEX IDX_BDEB8C68B7585238 (integer_id), PRIMARY KEY(software_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_source_code_repository (software_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_6060F0AAD7452741 (software_id), INDEX IDX_6060F0AA81CFDAE7 (url_id), PRIMARY KEY(software_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_operating_systems (software_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_E4AEAF2D7452741 (software_id), INDEX IDX_E4AEAF2B7585238 (integer_id), PRIMARY KEY(software_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_licenses (software_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_BD3AC270D7452741 (software_id), INDEX IDX_BD3AC270B7585238 (integer_id), PRIMARY KEY(software_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_features (software_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_7DC8115CD7452741 (software_id), INDEX IDX_7DC8115C698D3548 (text_id), PRIMARY KEY(software_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_software_value_languages (software_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_467007B5D7452741 (software_id), INDEX IDX_467007B582F1BAF4 (language_id), PRIMARY KEY(software_id, language_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_software_identity (id INT NOT NULL, data VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, is_addon TINYINT(1) NOT NULL, host_software_name VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD CONSTRAINT FK_226FED4DD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_software ADD CONSTRAINT FK_226FED4D4A8B36A0 FOREIGN KEY (screenshot_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_identity ADD CONSTRAINT FK_A89D248BD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_identity ADD CONSTRAINT FK_A89D248BBDCD912A FOREIGN KEY (softwareidentity_id) REFERENCES tbl_knowledge2_value_software_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_icon ADD CONSTRAINT FK_48797B0DD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_icon ADD CONSTRAINT FK_48797B0DEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_screenshot ADD CONSTRAINT FK_CAAD318ED7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_screenshot ADD CONSTRAINT FK_CAAD318EEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_authors ADD CONSTRAINT FK_513EC2EDD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_authors ADD CONSTRAINT FK_513EC2ED698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_last_version ADD CONSTRAINT FK_3E238B67D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_last_version ADD CONSTRAINT FK_3E238B67698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_publisher ADD CONSTRAINT FK_7A49818AD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_publisher ADD CONSTRAINT FK_7A49818A698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_website ADD CONSTRAINT FK_985DB55BD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_website ADD CONSTRAINT FK_985DB55B81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_description ADD CONSTRAINT FK_66ADAB28D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_description ADD CONSTRAINT FK_66ADAB28ABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_open_source ADD CONSTRAINT FK_BDEB8C68D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_open_source ADD CONSTRAINT FK_BDEB8C68B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_source_code_repository ADD CONSTRAINT FK_6060F0AAD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_source_code_repository ADD CONSTRAINT FK_6060F0AA81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_operating_systems ADD CONSTRAINT FK_E4AEAF2D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_operating_systems ADD CONSTRAINT FK_E4AEAF2B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_licenses ADD CONSTRAINT FK_BD3AC270D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_licenses ADD CONSTRAINT FK_BD3AC270B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_features ADD CONSTRAINT FK_7DC8115CD7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_features ADD CONSTRAINT FK_7DC8115C698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_languages ADD CONSTRAINT FK_467007B5D7452741 FOREIGN KEY (software_id) REFERENCES tbl_knowledge2_software (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_languages ADD CONSTRAINT FK_467007B582F1BAF4 FOREIGN KEY (language_id) REFERENCES tbl_knowledge2_value_language (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_software_identity ADD CONSTRAINT FK_DAB7854DBF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention ADD CONSTRAINT FK_E469668A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX idx_e46966834a3e1b6 TO IDX_E469668E6655814');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX entity_user_unique TO ENTITY_MENTIONED_USER_UNIQUE');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_knowledge_software_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_value CHANGE parent_entity_field parent_entity_field VARCHAR(25) NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a82c7c2cba TO IDX_772B09CB2C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_4bcb70a870f2bc06 TO IDX_772B09CB70F2BC06');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_identity DROP FOREIGN KEY FK_A89D248BD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_icon DROP FOREIGN KEY FK_48797B0DD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_screenshot DROP FOREIGN KEY FK_CAAD318ED7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_authors DROP FOREIGN KEY FK_513EC2EDD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_last_version DROP FOREIGN KEY FK_3E238B67D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_publisher DROP FOREIGN KEY FK_7A49818AD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_website DROP FOREIGN KEY FK_985DB55BD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_description DROP FOREIGN KEY FK_66ADAB28D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_open_source DROP FOREIGN KEY FK_BDEB8C68D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_source_code_repository DROP FOREIGN KEY FK_6060F0AAD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_operating_systems DROP FOREIGN KEY FK_E4AEAF2D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_licenses DROP FOREIGN KEY FK_BD3AC270D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_features DROP FOREIGN KEY FK_7DC8115CD7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_languages DROP FOREIGN KEY FK_467007B5D7452741');
        $this->addSql('ALTER TABLE tbl_knowledge2_software_value_identity DROP FOREIGN KEY FK_A89D248BBDCD912A');
        $this->addSql('DROP TABLE tbl_knowledge2_software');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_identity');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_icon');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_screenshot');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_authors');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_last_version');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_publisher');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_website');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_description');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_open_source');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_source_code_repository');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_operating_systems');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_licenses');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_features');
        $this->addSql('DROP TABLE tbl_knowledge2_software_value_languages');
        $this->addSql('DROP TABLE tbl_knowledge2_value_software_identity');
        $this->addSql('ALTER TABLE tbl_core_mention DROP FOREIGN KEY FK_E469668A76ED395');
        $this->addSql('ALTER TABLE tbl_core_mention CHANGE mentioned_user_id mentioned_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX idx_e469668e6655814 TO IDX_E46966834A3E1B6');
        $this->addSql('ALTER TABLE tbl_core_mention RENAME INDEX entity_mentioned_user_unique TO ENTITY_USER_UNIQUE');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_knowledge_software_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_value CHANGE parent_entity_field parent_entity_field VARCHAR(20) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb2c7c2cba TO IDX_4BCB70A82C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration RENAME INDEX idx_772b09cb70f2bc06 TO IDX_4BCB70A870F2BC06');
    }
}
