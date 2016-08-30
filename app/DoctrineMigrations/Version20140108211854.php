<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140108211854 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

		$this->addSql("CREATE INDEX IDX_DAA16454FBE2D86A ON tbl_wonder_creation_howto (howto_id)");
        $this->addSql("DROP INDEX idx_cad1c2db166d1f9c ON tbl_wonder_creation_howto");
		$this->addSql("CREATE INDEX IDX_EFDA5D91FDCE57C ON tbl_wonder_workshop_howto (workshop_id)");
        $this->addSql("DROP INDEX idx_a89f02a71fdce57c ON tbl_wonder_workshop_howto");
		$this->addSql("CREATE INDEX IDX_EFDA5D9FBE2D86A ON tbl_wonder_workshop_howto (howto_id)");
        $this->addSql("DROP INDEX idx_a89f02a7166d1f9c ON tbl_wonder_workshop_howto");
		$this->addSql("CREATE UNIQUE INDEX UNIQ_65A1D835989D9B62 ON tbl_howto (slug)");
		$this->addSql("DROP INDEX uniq_74e0d89a989d9b62 ON tbl_howto");
		$this->addSql("CREATE INDEX IDX_65A1D835A76ED395 ON tbl_howto (user_id)");
		$this->addSql("DROP INDEX idx_74e0d89aa76ed395 ON tbl_howto");
		$this->addSql("CREATE INDEX IDX_65A1D835D6BDC9DC ON tbl_howto (main_picture_id)");
		$this->addSql("DROP INDEX idx_74e0d89ad6bdc9dc ON tbl_howto");
		$this->addSql("CREATE UNIQUE INDEX UNIQ_65A1D835460F904B ON tbl_howto (license_id)");
		$this->addSql("DROP INDEX uniq_74e0d89a460f904b ON tbl_howto");
		$this->addSql("CREATE INDEX IDX_8049DA1DFBE2D86A ON tbl_howto_tag (howto_id)");
		$this->addSql("DROP INDEX idx_16938df8166d1f9c ON tbl_howto_tag");
		$this->addSql("CREATE INDEX IDX_8049DA1DBAD26311 ON tbl_howto_tag (tag_id)");
		$this->addSql("DROP INDEX idx_16938df8bad26311 ON tbl_howto_tag");
		$this->addSql("CREATE UNIQUE INDEX UNIQ_A0E1C556989D9B62 ON tbl_howto_article (slug)");
		$this->addSql("DROP INDEX uniq_4a08578d989d9b62 ON tbl_howto_article");
		$this->addSql("CREATE INDEX IDX_A0E1C556FBE2D86A ON tbl_howto_article (howto_id)");
		$this->addSql("DROP INDEX idx_4a08578d166d1f9c ON tbl_howto_article");
		$this->addSql("CREATE INDEX IDX_8BFDA5AC7294869C ON tbl_howto_article_body_picture (article_id)");
		$this->addSql("DROP INDEX idx_b2cf2b437294869c ON tbl_howto_article_body_picture");
		$this->addSql("CREATE INDEX IDX_8BFDA5ACEE45BDBF ON tbl_howto_article_body_picture (picture_id)");
		$this->addSql("DROP INDEX idx_b2cf2b43ee45bdbf ON tbl_howto_article_body_picture");
        $this->addSql("ALTER TABLE tbl_core_picture ADD sortIndex INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_core_user CHANGE fullname fullname VARCHAR(100) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_picture DROP sortIndex");
        $this->addSql("ALTER TABLE tbl_core_user CHANGE fullname fullname VARCHAR(100) DEFAULT ''");
        $this->addSql("DROP INDEX uniq_65a1d835989d9b62 ON tbl_howto");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_74E0D89A989D9B62 ON tbl_howto (slug)");
        $this->addSql("DROP INDEX uniq_65a1d835460f904b ON tbl_howto");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_74E0D89A460F904B ON tbl_howto (license_id)");
        $this->addSql("DROP INDEX idx_65a1d835a76ed395 ON tbl_howto");
        $this->addSql("CREATE INDEX IDX_74E0D89AA76ED395 ON tbl_howto (user_id)");
        $this->addSql("DROP INDEX idx_65a1d835d6bdc9dc ON tbl_howto");
        $this->addSql("CREATE INDEX IDX_74E0D89AD6BDC9DC ON tbl_howto (main_picture_id)");
        $this->addSql("DROP INDEX uniq_a0e1c556989d9b62 ON tbl_howto_article");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_4A08578D989D9B62 ON tbl_howto_article (slug)");
        $this->addSql("DROP INDEX idx_a0e1c556fbe2d86a ON tbl_howto_article");
        $this->addSql("CREATE INDEX IDX_4A08578D166D1F9C ON tbl_howto_article (howto_id)");
        $this->addSql("DROP INDEX idx_8bfda5ac7294869c ON tbl_howto_article_body_picture");
        $this->addSql("CREATE INDEX IDX_B2CF2B437294869C ON tbl_howto_article_body_picture (article_id)");
        $this->addSql("DROP INDEX idx_8bfda5acee45bdbf ON tbl_howto_article_body_picture");
        $this->addSql("CREATE INDEX IDX_B2CF2B43EE45BDBF ON tbl_howto_article_body_picture (picture_id)");
        $this->addSql("DROP INDEX idx_8049da1dfbe2d86a ON tbl_howto_tag");
        $this->addSql("CREATE INDEX IDX_16938DF8166D1F9C ON tbl_howto_tag (howto_id)");
        $this->addSql("DROP INDEX idx_8049da1dbad26311 ON tbl_howto_tag");
        $this->addSql("CREATE INDEX IDX_16938DF8BAD26311 ON tbl_howto_tag (tag_id)");
        $this->addSql("DROP INDEX idx_daa16454fbe2d86a ON tbl_wonder_creation_howto");
        $this->addSql("CREATE INDEX IDX_CAD1C2DB166D1F9C ON tbl_wonder_creation_howto (howto_id)");
        $this->addSql("DROP INDEX idx_efda5d91fdce57c ON tbl_wonder_workshop_howto");
        $this->addSql("CREATE INDEX IDX_A89F02A71FDCE57C ON tbl_wonder_workshop_howto (workshop_id)");
        $this->addSql("DROP INDEX idx_efda5d9fbe2d86a ON tbl_wonder_workshop_howto");
        $this->addSql("CREATE INDEX IDX_A89F02A7166D1F9C ON tbl_wonder_workshop_howto (howto_id)");
    }
}
