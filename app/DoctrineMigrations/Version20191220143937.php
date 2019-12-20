<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191220143937 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_offer (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, bodyExtract VARCHAR(255) NOT NULL, body_block_picture_count INT NOT NULL, body_block_video_count INT NOT NULL, location VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, is_draft TINYINT(1) NOT NULL, visibility INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_87B2CD21989D9B62 (slug), INDEX IDX_87B2CD21D6BDC9DC (main_picture_id), INDEX IDX_87B2CD21A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_offer_body_block (offer_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_B686524C53C674EE (offer_id), UNIQUE INDEX UNIQ_B686524CE9ED820C (block_id), PRIMARY KEY(offer_id, block_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_offer_picture (offer_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_AE41EC8B53C674EE (offer_id), INDEX IDX_AE41EC8BEE45BDBF (picture_id), PRIMARY KEY(offer_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_offer_tag (offer_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_6A34B98853C674EE (offer_id), INDEX IDX_6A34B988BAD26311 (tag_id), PRIMARY KEY(offer_id, tag_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_offer ADD CONSTRAINT FK_87B2CD21D6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_offer ADD CONSTRAINT FK_87B2CD21A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_offer_body_block ADD CONSTRAINT FK_B686524C53C674EE FOREIGN KEY (offer_id) REFERENCES tbl_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_body_block ADD CONSTRAINT FK_B686524CE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_picture ADD CONSTRAINT FK_AE41EC8B53C674EE FOREIGN KEY (offer_id) REFERENCES tbl_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_picture ADD CONSTRAINT FK_AE41EC8BEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_tag ADD CONSTRAINT FK_6A34B98853C674EE FOREIGN KEY (offer_id) REFERENCES tbl_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_tag ADD CONSTRAINT FK_6A34B988BAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD private_offer_count INT DEFAULT NULL, ADD public_offer_count INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_offer_body_block DROP FOREIGN KEY FK_B686524C53C674EE');
        $this->addSql('ALTER TABLE tbl_offer_picture DROP FOREIGN KEY FK_AE41EC8B53C674EE');
        $this->addSql('ALTER TABLE tbl_offer_tag DROP FOREIGN KEY FK_6A34B98853C674EE');
        $this->addSql('DROP TABLE tbl_offer');
        $this->addSql('DROP TABLE tbl_offer_body_block');
        $this->addSql('DROP TABLE tbl_offer_picture');
        $this->addSql('DROP TABLE tbl_offer_tag');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP private_offer_count, DROP public_offer_count');
    }
}
