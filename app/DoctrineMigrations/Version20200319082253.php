<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200319082253 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_event (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, bodyExtract VARCHAR(255) NOT NULL, body_block_picture_count INT NOT NULL, body_block_video_count INT NOT NULL, location VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, start_at DATETIME NOT NULL, start_date DATE NOT NULL, start_time TIME DEFAULT NULL, end_at DATETIME NOT NULL, end_date DATE DEFAULT NULL, end_time TIME DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, cancelled TINYINT(1) NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, private_collection_count INT NOT NULL, public_collection_count INT NOT NULL, view_count INT NOT NULL, join_count INT NOT NULL, is_draft TINYINT(1) NOT NULL, visibility INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_95CA40B8989D9B62 (slug), INDEX IDX_95CA40B8D6BDC9DC (main_picture_id), INDEX IDX_95CA40B8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_event_body_block (event_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_72C248B771F7E88B (event_id), UNIQUE INDEX UNIQ_72C248B7E9ED820C (block_id), PRIMARY KEY(event_id, block_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_event_picture (event_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_8A1E832D71F7E88B (event_id), INDEX IDX_8A1E832DEE45BDBF (picture_id), PRIMARY KEY(event_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_event_tag (event_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_57CE1DC371F7E88B (event_id), INDEX IDX_57CE1DC3BAD26311 (tag_id), PRIMARY KEY(event_id, tag_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_event ADD CONSTRAINT FK_95CA40B8D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_event ADD CONSTRAINT FK_95CA40B8A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_event_body_block ADD CONSTRAINT FK_72C248B771F7E88B FOREIGN KEY (event_id) REFERENCES tbl_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_event_body_block ADD CONSTRAINT FK_72C248B7E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_event_picture ADD CONSTRAINT FK_8A1E832D71F7E88B FOREIGN KEY (event_id) REFERENCES tbl_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_event_picture ADD CONSTRAINT FK_8A1E832DEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_event_tag ADD CONSTRAINT FK_57CE1DC371F7E88B FOREIGN KEY (event_id) REFERENCES tbl_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_event_tag ADD CONSTRAINT FK_57CE1DC3BAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_event_event_count INT NOT NULL, ADD private_event_count INT DEFAULT NULL, ADD public_event_count INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_event_body_block DROP FOREIGN KEY FK_72C248B771F7E88B');
        $this->addSql('ALTER TABLE tbl_event_picture DROP FOREIGN KEY FK_8A1E832D71F7E88B');
        $this->addSql('ALTER TABLE tbl_event_tag DROP FOREIGN KEY FK_57CE1DC371F7E88B');
        $this->addSql('DROP TABLE tbl_event');
        $this->addSql('DROP TABLE tbl_event_body_block');
        $this->addSql('DROP TABLE tbl_event_picture');
        $this->addSql('DROP TABLE tbl_event_tag');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP unlisted_event_event_count, DROP private_event_count, DROP public_event_count');
    }
}
