<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140424114032 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_find_content (id INT AUTO_INCREMENT NOT NULL, discr INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_find_content_gallery (id INT NOT NULL, location VARCHAR(100) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_find_data_gallery_picture (gallery_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_C4E072284E7AF8F (gallery_id), INDEX IDX_C4E07228EE45BDBF (picture_id), PRIMARY KEY(gallery_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_find_content_video (id INT NOT NULL, url VARCHAR(255) NOT NULL, kind SMALLINT NOT NULL, embedIdentifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_find_content_website (id INT NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_find_content_gallery ADD CONSTRAINT FK_3A9CF0FABF396750 FOREIGN KEY (id) REFERENCES tbl_find_content (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_find_data_gallery_picture ADD CONSTRAINT FK_C4E072284E7AF8F FOREIGN KEY (gallery_id) REFERENCES tbl_find_content_gallery (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_find_data_gallery_picture ADD CONSTRAINT FK_C4E07228EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_find_content_video ADD CONSTRAINT FK_BA2E7756BF396750 FOREIGN KEY (id) REFERENCES tbl_find_content (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_find_content_website ADD CONSTRAINT FK_3AD8D527BF396750 FOREIGN KEY (id) REFERENCES tbl_find_content (id) ON DELETE CASCADE");
        $this->addSql("DROP INDEX UNIQ_7C8E31ECF47645AE ON tbl_find");
        $this->addSql("ALTER TABLE tbl_find ADD content_id INT DEFAULT NULL, CHANGE url url VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_find ADD CONSTRAINT FK_7C8E31EC84A0A3ED FOREIGN KEY (content_id) REFERENCES tbl_find_content (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_7C8E31EC84A0A3ED ON tbl_find (content_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_find_content_gallery DROP FOREIGN KEY FK_3A9CF0FABF396750");
        $this->addSql("ALTER TABLE tbl_find_content_video DROP FOREIGN KEY FK_BA2E7756BF396750");
        $this->addSql("ALTER TABLE tbl_find_content_website DROP FOREIGN KEY FK_3AD8D527BF396750");
        $this->addSql("ALTER TABLE tbl_find DROP FOREIGN KEY FK_7C8E31EC84A0A3ED");
        $this->addSql("ALTER TABLE tbl_find_data_gallery_picture DROP FOREIGN KEY FK_C4E072284E7AF8F");
        $this->addSql("DROP TABLE tbl_find_content");
        $this->addSql("DROP TABLE tbl_find_content_gallery");
        $this->addSql("DROP TABLE tbl_find_data_gallery_picture");
        $this->addSql("DROP TABLE tbl_find_content_video");
        $this->addSql("DROP TABLE tbl_find_content_website");
        $this->addSql("DROP INDEX UNIQ_7C8E31EC84A0A3ED ON tbl_find");
        $this->addSql("ALTER TABLE tbl_find DROP content_id, CHANGE url url VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_7C8E31ECF47645AE ON tbl_find (url)");
    }
}
