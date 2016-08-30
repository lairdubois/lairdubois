<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140325172824 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_wonder_creation_body_block (creation_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_D91A44D334FFA69A (creation_id), UNIQUE INDEX UNIQ_D91A44D3E9ED820C (block_id), PRIMARY KEY(creation_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_workshop_body_block (workshop_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_DE6F6E5C1FDCE57C (workshop_id), UNIQUE INDEX UNIQ_DE6F6E5CE9ED820C (block_id), PRIMARY KEY(workshop_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_blog_post_body_block (post_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_6D9AC0DE4B89032C (post_id), UNIQUE INDEX UNIQ_6D9AC0DEE9ED820C (block_id), PRIMARY KEY(post_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_faq_question_body_block (question_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_3FD3049B1E27F6BF (question_id), UNIQUE INDEX UNIQ_3FD3049BE9ED820C (block_id), PRIMARY KEY(question_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_block (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, sort_index INT NOT NULL, discr INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_block_gallery (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_block_gallery_picture (gallery_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_8842BCD44E7AF8F (gallery_id), INDEX IDX_8842BCD4EE45BDBF (picture_id), PRIMARY KEY(gallery_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_block_text (id INT NOT NULL, body LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_block_video (id INT NOT NULL, url VARCHAR(255) NOT NULL, kind SMALLINT NOT NULL, embedIdentifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_howto_article_body_block (article_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_CA2443347294869C (article_id), UNIQUE INDEX UNIQ_CA244334E9ED820C (block_id), PRIMARY KEY(article_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_block ADD CONSTRAINT FK_D91A44D334FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_block ADD CONSTRAINT FK_D91A44D3E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_block ADD CONSTRAINT FK_DE6F6E5C1FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_block ADD CONSTRAINT FK_DE6F6E5CE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)");
        $this->addSql("ALTER TABLE tbl_blog_post_body_block ADD CONSTRAINT FK_6D9AC0DE4B89032C FOREIGN KEY (post_id) REFERENCES tbl_blog_post (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_blog_post_body_block ADD CONSTRAINT FK_6D9AC0DEE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)");
        $this->addSql("ALTER TABLE tbl_faq_question_body_block ADD CONSTRAINT FK_3FD3049B1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_faq_question (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_faq_question_body_block ADD CONSTRAINT FK_3FD3049BE9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)");
        $this->addSql("ALTER TABLE tbl_core_block_gallery ADD CONSTRAINT FK_AB12953DBF396750 FOREIGN KEY (id) REFERENCES tbl_core_block (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_core_block_gallery_picture ADD CONSTRAINT FK_8842BCD44E7AF8F FOREIGN KEY (gallery_id) REFERENCES tbl_core_block_gallery (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_core_block_gallery_picture ADD CONSTRAINT FK_8842BCD4EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_core_block_text ADD CONSTRAINT FK_1F8524D3BF396750 FOREIGN KEY (id) REFERENCES tbl_core_block (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_core_block_video ADD CONSTRAINT FK_663900D2BF396750 FOREIGN KEY (id) REFERENCES tbl_core_block (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_howto_article_body_block ADD CONSTRAINT FK_CA2443347294869C FOREIGN KEY (article_id) REFERENCES tbl_howto_article (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_howto_article_body_block ADD CONSTRAINT FK_CA244334E9ED820C FOREIGN KEY (block_id) REFERENCES tbl_core_block (id)");
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_faq_question CHANGE body body LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE body body LONGTEXT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_block DROP FOREIGN KEY FK_D91A44D3E9ED820C");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_block DROP FOREIGN KEY FK_DE6F6E5CE9ED820C");
        $this->addSql("ALTER TABLE tbl_blog_post_body_block DROP FOREIGN KEY FK_6D9AC0DEE9ED820C");
        $this->addSql("ALTER TABLE tbl_faq_question_body_block DROP FOREIGN KEY FK_3FD3049BE9ED820C");
        $this->addSql("ALTER TABLE tbl_core_block_gallery DROP FOREIGN KEY FK_AB12953DBF396750");
        $this->addSql("ALTER TABLE tbl_core_block_text DROP FOREIGN KEY FK_1F8524D3BF396750");
        $this->addSql("ALTER TABLE tbl_core_block_video DROP FOREIGN KEY FK_663900D2BF396750");
        $this->addSql("ALTER TABLE tbl_howto_article_body_block DROP FOREIGN KEY FK_CA244334E9ED820C");
        $this->addSql("ALTER TABLE tbl_core_block_gallery_picture DROP FOREIGN KEY FK_8842BCD44E7AF8F");
        $this->addSql("DROP TABLE tbl_wonder_creation_body_block");
        $this->addSql("DROP TABLE tbl_wonder_workshop_body_block");
        $this->addSql("DROP TABLE tbl_blog_post_body_block");
        $this->addSql("DROP TABLE tbl_faq_question_body_block");
        $this->addSql("DROP TABLE tbl_core_block");
        $this->addSql("DROP TABLE tbl_core_block_gallery");
        $this->addSql("DROP TABLE tbl_core_block_gallery_picture");
        $this->addSql("DROP TABLE tbl_core_block_text");
        $this->addSql("DROP TABLE tbl_core_block_video");
        $this->addSql("DROP TABLE tbl_howto_article_body_block");
        $this->addSql("ALTER TABLE tbl_blog_post CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("ALTER TABLE tbl_faq_question CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("ALTER TABLE tbl_howto_article CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("ALTER TABLE tbl_wonder_creation CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("ALTER TABLE tbl_wonder_plan CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE body body LONGTEXT NOT NULL COLLATE utf8_unicode_ci");
    }
}
