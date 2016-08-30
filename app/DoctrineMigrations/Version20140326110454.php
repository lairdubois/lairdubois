<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140326110454 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_blog_post_body_picture");
        $this->addSql("DROP TABLE tbl_faq_question_body_picture");
        $this->addSql("DROP TABLE tbl_howto_article_body_picture");
        $this->addSql("DROP TABLE tbl_wonder_creation_body_picture");
        $this->addSql("DROP TABLE tbl_wonder_workshop_body_picture");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_blog_post_body_picture (post_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_5BDDCBEE4B89032C (post_id), INDEX IDX_5BDDCBEEEE45BDBF (picture_id), PRIMARY KEY(post_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_faq_question_body_picture (question_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_4ADA244E1E27F6BF (question_id), INDEX IDX_4ADA244EEE45BDBF (picture_id), PRIMARY KEY(question_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_howto_article_body_picture (article_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_8BFDA5AC7294869C (article_id), INDEX IDX_8BFDA5ACEE45BDBF (picture_id), PRIMARY KEY(article_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_creation_body_picture (creation_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_4A6461A34FFA69A (creation_id), INDEX IDX_4A6461AEE45BDBF (picture_id), PRIMARY KEY(creation_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_workshop_body_picture (workshop_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_63060C3D1FDCE57C (workshop_id), INDEX IDX_63060C3DEE45BDBF (picture_id), PRIMARY KEY(workshop_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_blog_post_body_picture ADD CONSTRAINT FK_5BDDCBEE4B89032C FOREIGN KEY (post_id) REFERENCES tbl_blog_post (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_blog_post_body_picture ADD CONSTRAINT FK_5BDDCBEEEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_faq_question_body_picture ADD CONSTRAINT FK_4ADA244E1E27F6BF FOREIGN KEY (question_id) REFERENCES tbl_faq_question (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_faq_question_body_picture ADD CONSTRAINT FK_4ADA244EEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_howto_article_body_picture ADD CONSTRAINT FK_B2CF2B437294869C FOREIGN KEY (article_id) REFERENCES tbl_howto_article (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_howto_article_body_picture ADD CONSTRAINT FK_B2CF2B43EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_picture ADD CONSTRAINT FK_4A6461A34FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_picture ADD CONSTRAINT FK_4A6461AEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_picture ADD CONSTRAINT FK_63060C3D1FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_picture ADD CONSTRAINT FK_63060C3DEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
    }
}
