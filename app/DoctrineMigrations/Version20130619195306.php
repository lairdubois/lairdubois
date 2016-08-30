<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130619195306 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_wonder_creation_body_picture (creation_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_4A6461A34FFA69A (creation_id), INDEX IDX_4A6461AEE45BDBF (picture_id), PRIMARY KEY(creation_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_workshop_body_picture (workshop_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_63060C3D1FDCE57C (workshop_id), INDEX IDX_63060C3DEE45BDBF (picture_id), PRIMARY KEY(workshop_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_picture ADD CONSTRAINT FK_4A6461A34FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_creation_body_picture ADD CONSTRAINT FK_4A6461AEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_picture ADD CONSTRAINT FK_63060C3D1FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_body_picture ADD CONSTRAINT FK_63060C3DEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");

		// Rename tables /////

		$this->addSql("ALTER TABLE tbl_howto_article_picture RENAME TO tbl_howto_article_body_picture");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_wonder_creation_body_picture");
        $this->addSql("DROP TABLE tbl_wonder_workshop_body_picture");

		// Rename tables /////

		$this->addSql("ALTER TABLE tbl_howto_article_body_picture RENAME TO tbl_howto_article_picture");

	}
}
