<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130528125700 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");


		// Project --> Howto


		// Drop forein keys /////

		$this->addSql("ALTER TABLE tbl_project_article DROP FOREIGN KEY FK_4A08578D166D1F9C");
		$this->addSql("ALTER TABLE tbl_project_tag DROP FOREIGN KEY FK_16938DF8166D1F9C");

		$this->addSql("ALTER TABLE tbl_wonder_creation_project DROP FOREIGN KEY FK_CAD1C2DB166D1F9C");
		$this->addSql("ALTER TABLE tbl_wonder_workshop_project DROP FOREIGN KEY FK_A89F02A7166D1F9C");

        // Rename tables /////

		$this->addSql("ALTER TABLE tbl_project RENAME TO tbl_howto");
        $this->addSql("ALTER TABLE tbl_project_article RENAME TO tbl_howto_article");
        $this->addSql("ALTER TABLE tbl_project_article_picture RENAME TO tbl_howto_article_picture");
        $this->addSql("ALTER TABLE tbl_project_tag RENAME TO tbl_howto_tag");

        $this->addSql("ALTER TABLE tbl_wonder_creation_project RENAME TO tbl_wonder_creation_howto");
        $this->addSql("ALTER TABLE tbl_wonder_workshop_project RENAME TO tbl_wonder_workshop_howto");

		// Rename fields /////

		$this->addSql("ALTER TABLE tbl_howto_article CHANGE project_id howto_id INT NOT NULL");
		$this->addSql("ALTER TABLE tbl_howto_tag CHANGE project_id howto_id INT NOT NULL");

		$this->addSql("ALTER TABLE tbl_wonder_creation CHANGE project_count howto_count INT NOT NULL");
		$this->addSql("ALTER TABLE tbl_wonder_creation_howto CHANGE project_id howto_id INT NOT NULL");
		$this->addSql("ALTER TABLE tbl_wonder_workshop CHANGE project_count howto_count INT NOT NULL");
		$this->addSql("ALTER TABLE tbl_wonder_workshop_howto CHANGE project_id howto_id INT NOT NULL");

		$this->addSql("ALTER TABLE tbl_core_user CHANGE draft_project_count draft_howto_count INT DEFAULT NULL");
		$this->addSql("ALTER TABLE tbl_core_user CHANGE published_project_count published_howto_count INT DEFAULT NULL");

		// Restore forein keys /////

		$this->addSql("ALTER TABLE tbl_howto_article ADD CONSTRAINT FK_A0E1C556FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id)");
		$this->addSql("ALTER TABLE tbl_howto_tag ADD CONSTRAINT FK_8049DA1DFBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE");

		$this->addSql("ALTER TABLE tbl_wonder_creation_howto ADD CONSTRAINT FK_DAA16454FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE");
		$this->addSql("ALTER TABLE tbl_wonder_workshop_howto ADD CONSTRAINT FK_EFDA5D9FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

		// DOWN impossible !!

    }
}
