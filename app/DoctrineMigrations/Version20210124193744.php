<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210124193744 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_tool (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, completion_100 INT NOT NULL, contributor_count INT NOT NULL, positive_vote_count INT NOT NULL, negative_vote_count INT NOT NULL, vote_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, private_collection_count INT NOT NULL, public_collection_count INT NOT NULL, view_count INT NOT NULL, name VARCHAR(100) DEFAULT NULL, is_product TINYINT(1) NOT NULL, product_name VARCHAR(100) DEFAULT NULL, identity VARCHAR(100) DEFAULT NULL, identity_rejected TINYINT(1) NOT NULL, photo_rejected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_364BC63F989D9B62 (slug), INDEX IDX_364BC63FD6BDC9DC (main_picture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_identity (tool_id INT NOT NULL, toolidentity_id INT NOT NULL, INDEX IDX_F8C2FB638F7B22CC (tool_id), INDEX IDX_F8C2FB6311D8CC51 (toolidentity_id), PRIMARY KEY(tool_id, toolidentity_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_tool_value_photo (tool_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_A17CB9848F7B22CC (tool_id), INDEX IDX_A17CB984EE45BDBF (picture_id), PRIMARY KEY(tool_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_tool_identity (id INT NOT NULL, data VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, is_product TINYINT(1) NOT NULL, productName VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool ADD CONSTRAINT FK_364BC63FD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_identity ADD CONSTRAINT FK_F8C2FB638F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_identity ADD CONSTRAINT FK_F8C2FB6311D8CC51 FOREIGN KEY (toolidentity_id) REFERENCES tbl_knowledge2_value_tool_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo ADD CONSTRAINT FK_A17CB9848F7B22CC FOREIGN KEY (tool_id) REFERENCES tbl_knowledge2_tool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo ADD CONSTRAINT FK_A17CB984EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_knowledge2_value_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_tool_identity ADD CONSTRAINT FK_5BFDAF3CBF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_identity DROP FOREIGN KEY FK_F8C2FB638F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_photo DROP FOREIGN KEY FK_A17CB9848F7B22CC');
        $this->addSql('ALTER TABLE tbl_knowledge2_tool_value_identity DROP FOREIGN KEY FK_F8C2FB6311D8CC51');
        $this->addSql('DROP TABLE tbl_knowledge2_tool');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_identity');
        $this->addSql('DROP TABLE tbl_knowledge2_tool_value_photo');
        $this->addSql('DROP TABLE tbl_knowledge2_value_tool_identity');
    }
}
