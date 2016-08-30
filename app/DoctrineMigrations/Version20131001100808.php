<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131001100808 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_blog_post (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, main_picture_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_draft TINYINT(1) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, UNIQUE INDEX UNIQ_FFD28F8E989D9B62 (slug), INDEX IDX_FFD28F8EA76ED395 (user_id), INDEX IDX_FFD28F8ED6BDC9DC (main_picture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_blog_post_body_picture (post_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_5BDDCBEE4B89032C (post_id), INDEX IDX_5BDDCBEEEE45BDBF (picture_id), PRIMARY KEY(post_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_blog_post_tag (post_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_37017BDC4B89032C (post_id), INDEX IDX_37017BDCBAD26311 (tag_id), PRIMARY KEY(post_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_blog_post ADD CONSTRAINT FK_FFD28F8EA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)");
        $this->addSql("ALTER TABLE tbl_blog_post ADD CONSTRAINT FK_FFD28F8ED6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)");
        $this->addSql("ALTER TABLE tbl_blog_post_body_picture ADD CONSTRAINT FK_5BDDCBEE4B89032C FOREIGN KEY (post_id) REFERENCES tbl_blog_post (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_blog_post_body_picture ADD CONSTRAINT FK_5BDDCBEEEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_blog_post_tag ADD CONSTRAINT FK_37017BDC4B89032C FOREIGN KEY (post_id) REFERENCES tbl_blog_post (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_blog_post_tag ADD CONSTRAINT FK_37017BDCBAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_blog_post_body_picture DROP FOREIGN KEY FK_5BDDCBEE4B89032C");
        $this->addSql("ALTER TABLE tbl_blog_post_tag DROP FOREIGN KEY FK_37017BDC4B89032C");
        $this->addSql("DROP TABLE tbl_blog_post");
        $this->addSql("DROP TABLE tbl_blog_post_body_picture");
        $this->addSql("DROP TABLE tbl_blog_post_tag");
    }
}
