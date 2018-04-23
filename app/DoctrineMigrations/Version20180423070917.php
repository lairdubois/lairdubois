<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180423070917 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, back_cover_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, contributor_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, title_rejected TINYINT(1) NOT NULL, cover_rejected TINYINT(1) NOT NULL, author VARCHAR(255) DEFAULT NULL, editor VARCHAR(255) DEFAULT NULL, collection VARCHAR(255) DEFAULT NULL, catalogLink VARCHAR(255) DEFAULT NULL, summary LONGTEXT DEFAULT NULL, subjects LONGTEXT DEFAULT NULL, language VARCHAR(255) DEFAULT NULL, translated TINYINT(1) DEFAULT NULL, pageCount INT DEFAULT NULL, isbn VARCHAR(20) DEFAULT NULL, publishYear INT DEFAULT NULL, price VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_DD5D5BDF989D9B62 (slug), INDEX IDX_DD5D5BDFD6BDC9DC (main_picture_id), INDEX IDX_DD5D5BDF68F37169 (back_cover_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_title (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_563814D516A2B381 (book_id), INDEX IDX_563814D5698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_cover (book_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_F006EA7B16A2B381 (book_id), INDEX IDX_F006EA7BEE45BDBF (picture_id), PRIMARY KEY(book_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_back_cover (book_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_52AF891E16A2B381 (book_id), INDEX IDX_52AF891EEE45BDBF (picture_id), PRIMARY KEY(book_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_author (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_910B482F16A2B381 (book_id), INDEX IDX_910B482F698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_editor (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_E055615D16A2B381 (book_id), INDEX IDX_E055615D698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_collection (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_B245B14D16A2B381 (book_id), INDEX IDX_B245B14D698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_catalog_link (book_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_6A00147B16A2B381 (book_id), INDEX IDX_6A00147B81CFDAE7 (url_id), PRIMARY KEY(book_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_summary (book_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_F06AB52816A2B381 (book_id), INDEX IDX_F06AB528ABCBF34C (longtext_id), PRIMARY KEY(book_id, longtext_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_subjects (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_4A1543DC16A2B381 (book_id), INDEX IDX_4A1543DC698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_language (book_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_35EBAB7E16A2B381 (book_id), INDEX IDX_35EBAB7E82F1BAF4 (language_id), PRIMARY KEY(book_id, language_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_translated (book_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_18FE6A4F16A2B381 (book_id), INDEX IDX_18FE6A4FB7585238 (integer_id), PRIMARY KEY(book_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_page_count (book_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_AD506FCB16A2B381 (book_id), INDEX IDX_AD506FCBB7585238 (integer_id), PRIMARY KEY(book_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_isbn (book_id INT NOT NULL, isbn_id INT NOT NULL, INDEX IDX_C5EEFB6916A2B381 (book_id), INDEX IDX_C5EEFB69AFFF1118 (isbn_id), PRIMARY KEY(book_id, isbn_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_publish_year (book_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_40B02A0B16A2B381 (book_id), INDEX IDX_40B02A0BB7585238 (integer_id), PRIMARY KEY(book_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_price (book_id INT NOT NULL, price_id INT NOT NULL, INDEX IDX_B7C64E6716A2B381 (book_id), INDEX IDX_B7C64E67D614C7E7 (price_id), PRIMARY KEY(book_id, price_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_isbn (id INT NOT NULL, data VARCHAR(255) NOT NULL, rawIsbn VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_language (id INT NOT NULL, data VARCHAR(255) NOT NULL, rawLanguage VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_price (id INT NOT NULL, data VARCHAR(20) NOT NULL, rawPrice DOUBLE PRECISION NOT NULL, currency VARCHAR(3) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD CONSTRAINT FK_DD5D5BDFD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD CONSTRAINT FK_DD5D5BDF68F37169 FOREIGN KEY (back_cover_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_title ADD CONSTRAINT FK_563814D516A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_title ADD CONSTRAINT FK_563814D5698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_cover ADD CONSTRAINT FK_F006EA7B16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_cover ADD CONSTRAINT FK_F006EA7BEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_back_cover ADD CONSTRAINT FK_52AF891E16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_back_cover ADD CONSTRAINT FK_52AF891EEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_author ADD CONSTRAINT FK_910B482F16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_author ADD CONSTRAINT FK_910B482F698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_editor ADD CONSTRAINT FK_E055615D16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_editor ADD CONSTRAINT FK_E055615D698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_collection ADD CONSTRAINT FK_B245B14D16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_collection ADD CONSTRAINT FK_B245B14D698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_catalog_link ADD CONSTRAINT FK_6A00147B16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_catalog_link ADD CONSTRAINT FK_6A00147B81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_summary ADD CONSTRAINT FK_F06AB52816A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_summary ADD CONSTRAINT FK_F06AB528ABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_subjects ADD CONSTRAINT FK_4A1543DC16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_subjects ADD CONSTRAINT FK_4A1543DC698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_language ADD CONSTRAINT FK_35EBAB7E16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_language ADD CONSTRAINT FK_35EBAB7E82F1BAF4 FOREIGN KEY (language_id) REFERENCES tbl_knowledge2_value_language (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_translated ADD CONSTRAINT FK_18FE6A4F16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_translated ADD CONSTRAINT FK_18FE6A4FB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_page_count ADD CONSTRAINT FK_AD506FCB16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_page_count ADD CONSTRAINT FK_AD506FCBB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_isbn ADD CONSTRAINT FK_C5EEFB6916A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_isbn ADD CONSTRAINT FK_C5EEFB69AFFF1118 FOREIGN KEY (isbn_id) REFERENCES tbl_knowledge2_value_isbn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_publish_year ADD CONSTRAINT FK_40B02A0B16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_publish_year ADD CONSTRAINT FK_40B02A0BB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_price ADD CONSTRAINT FK_B7C64E6716A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_price ADD CONSTRAINT FK_B7C64E67D614C7E7 FOREIGN KEY (price_id) REFERENCES tbl_knowledge2_value_price (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_isbn ADD CONSTRAINT FK_F19477E6BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_language ADD CONSTRAINT FK_9C9A8A3EBF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_price ADD CONSTRAINT FK_CAF5AA5ABF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_knowledge_book_count INT NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_title DROP FOREIGN KEY FK_563814D516A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_cover DROP FOREIGN KEY FK_F006EA7B16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_back_cover DROP FOREIGN KEY FK_52AF891E16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_author DROP FOREIGN KEY FK_910B482F16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_editor DROP FOREIGN KEY FK_E055615D16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_collection DROP FOREIGN KEY FK_B245B14D16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_catalog_link DROP FOREIGN KEY FK_6A00147B16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_summary DROP FOREIGN KEY FK_F06AB52816A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_subjects DROP FOREIGN KEY FK_4A1543DC16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_language DROP FOREIGN KEY FK_35EBAB7E16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_translated DROP FOREIGN KEY FK_18FE6A4F16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_page_count DROP FOREIGN KEY FK_AD506FCB16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_isbn DROP FOREIGN KEY FK_C5EEFB6916A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_publish_year DROP FOREIGN KEY FK_40B02A0B16A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_price DROP FOREIGN KEY FK_B7C64E6716A2B381');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_isbn DROP FOREIGN KEY FK_C5EEFB69AFFF1118');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_language DROP FOREIGN KEY FK_35EBAB7E82F1BAF4');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_price DROP FOREIGN KEY FK_B7C64E67D614C7E7');
        $this->addSql('DROP TABLE tbl_knowledge2_book');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_title');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_cover');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_back_cover');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_author');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_editor');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_collection');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_catalog_link');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_summary');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_subjects');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_language');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_translated');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_page_count');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_isbn');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_publish_year');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_price');
        $this->addSql('DROP TABLE tbl_knowledge2_value_isbn');
        $this->addSql('DROP TABLE tbl_knowledge2_value_language');
        $this->addSql('DROP TABLE tbl_knowledge2_value_price');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_knowledge_book_count');
    }
}
