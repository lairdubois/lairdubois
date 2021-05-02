<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210502123720 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, manual_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, completion_100 INT NOT NULL, contributor_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, private_collection_count INT NOT NULL, public_collection_count INT NOT NULL, view_count INT NOT NULL, name VARCHAR(255) DEFAULT NULL, name_rejected TINYINT(1) NOT NULL, photo_rejected TINYINT(1) NOT NULL, brand VARCHAR(255) DEFAULT NULL, product_name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, family LONGTEXT DEFAULT NULL, power_supply LONGTEXT DEFAULT NULL, power LONGTEXT DEFAULT NULL, weight LONGTEXT DEFAULT NULL, docs LONGTEXT DEFAULT NULL, catalog_link LONGTEXT DEFAULT NULL, video VARCHAR(255) DEFAULT NULL, review_count INT NOT NULL, average_rating DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_364BC63F989D9B62 (slug), INDEX IDX_364BC63FD6BDC9DC (main_picture_id), INDEX IDX_364BC63F9BA073D6 (manual_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_name (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_45D1D3BB8F7B22CC (tool_id), INDEX IDX_45D1D3BB698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_photo (tool_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_A17CB9848F7B22CC (tool_id), INDEX IDX_A17CB984EE45BDBF (picture_id), PRIMARY KEY(tool_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_manual (tool_id INT NOT NULL, pdf_id INT NOT NULL, INDEX IDX_E9D7AA968F7B22CC (tool_id), INDEX IDX_E9D7AA96511FC912 (pdf_id), PRIMARY KEY(tool_id, pdf_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_brand (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_A999C4C48F7B22CC (tool_id), INDEX IDX_A999C4C4698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_product_name (tool_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_F72E33FE8F7B22CC (tool_id), INDEX IDX_F72E33FE698D3548 (text_id), PRIMARY KEY(tool_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_description (tool_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_3C28BFD58F7B22CC (tool_id), INDEX IDX_3C28BFD5ABCBF34C (longtext_id), PRIMARY KEY(tool_id, longtext_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_family (tool_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_5CEA35098F7B22CC (tool_id), INDEX IDX_5CEA3509B7585238 (integer_id), PRIMARY KEY(tool_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_power_supply (tool_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_E2042AAA8F7B22CC (tool_id), INDEX IDX_E2042AAAB7585238 (integer_id), PRIMARY KEY(tool_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_power (tool_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_1E413C3C8F7B22CC (tool_id), INDEX IDX_1E413C3CB7585238 (integer_id), PRIMARY KEY(tool_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_weight (tool_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_FEC141138F7B22CC (tool_id), INDEX IDX_FEC14113B7585238 (integer_id), PRIMARY KEY(tool_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_docs (tool_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_4AA5860A8F7B22CC (tool_id), INDEX IDX_4AA5860A81CFDAE7 (url_id), PRIMARY KEY(tool_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_catalog_link (tool_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_67FC46DE8F7B22CC (tool_id), INDEX IDX_67FC46DE81CFDAE7 (url_id), PRIMARY KEY(tool_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_video (tool_id INT NOT NULL, video_id INT NOT NULL, INDEX IDX_C90CE7B08F7B22CC (tool_id), INDEX IDX_C90CE7B029C1004E (video_id), PRIMARY KEY(tool_id, video_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_pdf (id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_5EE60CE989329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63FD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63F9BA073D6 FOREIGN KEY (manual_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_name ADD CONSTRAINT FK_45D1D3BB8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_name ADD CONSTRAINT FK_45D1D3BB698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo ADD CONSTRAINT FK_A17CB9848F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo ADD CONSTRAINT FK_A17CB984EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual ADD CONSTRAINT FK_E9D7AA968F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual ADD CONSTRAINT FK_E9D7AA96511FC912 FOREIGN KEY (pdf_id) REFERENCES tbl_knowledge2_value_pdf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_brand ADD CONSTRAINT FK_A999C4C48F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_brand ADD CONSTRAINT FK_A999C4C4698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_product_name ADD CONSTRAINT FK_F72E33FE8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_product_name ADD CONSTRAINT FK_F72E33FE698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_description ADD CONSTRAINT FK_3C28BFD58F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_description ADD CONSTRAINT FK_3C28BFD5ABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD CONSTRAINT FK_5CEA35098F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family ADD CONSTRAINT FK_5CEA3509B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power_supply ADD CONSTRAINT FK_E2042AAA8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power_supply ADD CONSTRAINT FK_E2042AAAB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power ADD CONSTRAINT FK_1E413C3C8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power ADD CONSTRAINT FK_1E413C3CB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD CONSTRAINT FK_FEC141138F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight ADD CONSTRAINT FK_FEC14113B7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_docs ADD CONSTRAINT FK_4AA5860A8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_docs ADD CONSTRAINT FK_4AA5860A81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_catalog_link ADD CONSTRAINT FK_67FC46DE8F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_catalog_link ADD CONSTRAINT FK_67FC46DE81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_video ADD CONSTRAINT FK_C90CE7B08F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_video ADD CONSTRAINT FK_C90CE7B029C1004E FOREIGN KEY (video_id) REFERENCES tbl_knowledge2_value_video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_pdf ADD CONSTRAINT FK_5EE60CE989329D25 FOREIGN KEY (resource_id) REFERENCES tbl_core_resource (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_pdf ADD CONSTRAINT FK_5EE60CE9BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD knowledge_tools_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD unlisted_knowledge_tool_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_name DROP FOREIGN KEY FK_45D1D3BB8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo DROP FOREIGN KEY FK_A17CB9848F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual DROP FOREIGN KEY FK_E9D7AA968F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_brand DROP FOREIGN KEY FK_A999C4C48F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_product_name DROP FOREIGN KEY FK_F72E33FE8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_description DROP FOREIGN KEY FK_3C28BFD58F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_family DROP FOREIGN KEY FK_5CEA35098F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power_supply DROP FOREIGN KEY FK_E2042AAA8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_power DROP FOREIGN KEY FK_1E413C3C8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_weight DROP FOREIGN KEY FK_FEC141138F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_docs DROP FOREIGN KEY FK_4AA5860A8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_catalog_link DROP FOREIGN KEY FK_67FC46DE8F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_video DROP FOREIGN KEY FK_C90CE7B08F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_manual DROP FOREIGN KEY FK_E9D7AA96511FC912');
        $this->addSql('DROP TABLE tbl_knowledge2_tool');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_name');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_photo');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_manual');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_brand');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_product_name');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_description');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_family');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_power_supply');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_power');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_weight');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_docs');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_catalog_link');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_video');
        $this->addSql('DROP TABLE tbl_knowledge2_value_pdf');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP knowledge_tools_badge_enabled, DROP unlisted_knowledge_tool_count');
    }
}
