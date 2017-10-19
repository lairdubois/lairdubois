<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170923151716 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_testify (id INT NOT NULL, testimonial_id INT NOT NULL, INDEX IDX_60083261D4EC6B1 (testimonial_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_testimonial (id INT AUTO_INCREMENT NOT NULL, school_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, diploma VARCHAR(255) DEFAULT NULL, from_year INT NOT NULL, to_year INT DEFAULT NULL, body LONGTEXT DEFAULT NULL, html_body LONGTEXT DEFAULT NULL, INDEX IDX_5B26160C32A47EE (school_id), INDEX IDX_5B26160A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, photo_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, contributor_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, name VARCHAR(100) DEFAULT NULL, name_rejected TINYINT(1) NOT NULL, logo_rejected TINYINT(1) NOT NULL, website VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, geographicalAreas LONGTEXT DEFAULT NULL, postalCode VARCHAR(20) DEFAULT NULL, locality VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, description LONGTEXT DEFAULT NULL, public TINYINT(1) DEFAULT NULL, birthYear INT DEFAULT NULL, diplomas LONGTEXT DEFAULT NULL, trainingTypes LONGTEXT DEFAULT NULL, testimonial_count INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_draft TINYINT(1) NOT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8A05684F989D9B62 (slug), INDEX IDX_8A05684FD6BDC9DC (main_picture_id), INDEX IDX_8A05684F7E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_name (school_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_9B6BA057C32A47EE (school_id), INDEX IDX_9B6BA057698D3548 (text_id), PRIMARY KEY(school_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_logo (school_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_21C64442C32A47EE (school_id), INDEX IDX_21C64442EE45BDBF (picture_id), PRIMARY KEY(school_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_photo (school_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_81EADA4C32A47EE (school_id), INDEX IDX_81EADA4EE45BDBF (picture_id), PRIMARY KEY(school_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_website (school_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_4DAD3896C32A47EE (school_id), INDEX IDX_4DAD389681CFDAE7 (url_id), PRIMARY KEY(school_id, url_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_address (school_id INT NOT NULL, location_id INT NOT NULL, INDEX IDX_78C0AF0C32A47EE (school_id), INDEX IDX_78C0AF064D218E (location_id), PRIMARY KEY(school_id, location_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_phone (school_id INT NOT NULL, phone_id INT NOT NULL, INDEX IDX_58E6BE61C32A47EE (school_id), INDEX IDX_58E6BE613B7323CB (phone_id), PRIMARY KEY(school_id, phone_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_description (school_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_E962D2C6C32A47EE (school_id), INDEX IDX_E962D2C6ABCBF34C (longtext_id), PRIMARY KEY(school_id, longtext_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_public (school_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_F97F7893C32A47EE (school_id), INDEX IDX_F97F7893B7585238 (integer_id), PRIMARY KEY(school_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_birth_year (school_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_CCFBAE04C32A47EE (school_id), INDEX IDX_CCFBAE04B7585238 (integer_id), PRIMARY KEY(school_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_diplomas (school_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_C9E5A91AC32A47EE (school_id), INDEX IDX_C9E5A91A698D3548 (text_id), PRIMARY KEY(school_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_training_types (school_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_58A41DF4C32A47EE (school_id), INDEX IDX_58A41DF4B7585238 (integer_id), PRIMARY KEY(school_id, integer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_testify ADD CONSTRAINT FK_60083261D4EC6B1 FOREIGN KEY (testimonial_id) REFERENCES tbl_knowledge2_school_testimonial (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_testify ADD CONSTRAINT FK_6008326BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_testimonial ADD CONSTRAINT FK_5B26160C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_testimonial ADD CONSTRAINT FK_5B26160A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD CONSTRAINT FK_8A05684FD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD CONSTRAINT FK_8A05684F7E9E4C8C FOREIGN KEY (photo_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_name ADD CONSTRAINT FK_9B6BA057C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_name ADD CONSTRAINT FK_9B6BA057698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_logo ADD CONSTRAINT FK_21C64442C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_logo ADD CONSTRAINT FK_21C64442EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_photo ADD CONSTRAINT FK_81EADA4C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_photo ADD CONSTRAINT FK_81EADA4EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_website ADD CONSTRAINT FK_4DAD3896C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_website ADD CONSTRAINT FK_4DAD389681CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_address ADD CONSTRAINT FK_78C0AF0C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_address ADD CONSTRAINT FK_78C0AF064D218E FOREIGN KEY (location_id) REFERENCES tbl_knowledge2_value_location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_phone ADD CONSTRAINT FK_58E6BE61C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_phone ADD CONSTRAINT FK_58E6BE613B7323CB FOREIGN KEY (phone_id) REFERENCES tbl_knowledge2_value_phone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_description ADD CONSTRAINT FK_E962D2C6C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_description ADD CONSTRAINT FK_E962D2C6ABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_public ADD CONSTRAINT FK_F97F7893C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_public ADD CONSTRAINT FK_F97F7893B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_birth_year ADD CONSTRAINT FK_CCFBAE04C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_birth_year ADD CONSTRAINT FK_CCFBAE04B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_diplomas ADD CONSTRAINT FK_C9E5A91AC32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_diplomas ADD CONSTRAINT FK_C9E5A91A698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_training_types ADD CONSTRAINT FK_58A41DF4C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_training_types ADD CONSTRAINT FK_58A41DF4B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_tag CHANGE name `label` VARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user ADD testimonial_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_knowledge_school_count INT NOT NULL, ADD education_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_testify DROP FOREIGN KEY FK_60083261D4EC6B1');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_testimonial DROP FOREIGN KEY FK_5B26160C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_name DROP FOREIGN KEY FK_9B6BA057C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_logo DROP FOREIGN KEY FK_21C64442C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_photo DROP FOREIGN KEY FK_81EADA4C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_website DROP FOREIGN KEY FK_4DAD3896C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_address DROP FOREIGN KEY FK_78C0AF0C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_phone DROP FOREIGN KEY FK_58E6BE61C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_description DROP FOREIGN KEY FK_E962D2C6C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_public DROP FOREIGN KEY FK_F97F7893C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_birth_year DROP FOREIGN KEY FK_CCFBAE04C32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_diplomas DROP FOREIGN KEY FK_C9E5A91AC32A47EE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_training_types DROP FOREIGN KEY FK_58A41DF4C32A47EE');
        $this->addSql('DROP TABLE tbl_core_activity_testify');
        $this->addSql('DROP TABLE tbl_knowledge2_school_testimonial');
        $this->addSql('DROP TABLE tbl_knowledge2_school');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_name');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_logo');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_photo');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_website');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_address');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_phone');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_description');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_public');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_birth_year');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_diplomas');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_training_types');
        $this->addSql('ALTER TABLE tbl_core_tag CHANGE `label` name VARCHAR(40) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_core_user DROP testimonial_count');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_knowledge_school_count, DROP education_count');
    }
}
