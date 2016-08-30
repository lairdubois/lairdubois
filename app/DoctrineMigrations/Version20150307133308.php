<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150307133308 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_wonder_referal_referral');
        $this->addSql('ALTER TABLE tbl_core_comment_picture ADD CONSTRAINT FK_44BD49B7F8697D13 FOREIGN KEY (comment_id) REFERENCES tbl_core_comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_comment_picture ADD CONSTRAINT FK_44BD49B7EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_find_content_video ADD thumbnail_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_find_content_video ADD CONSTRAINT FK_BA2E7756FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_BA2E7756FDFF2E92 ON tbl_find_content_video (thumbnail_id)');
        $this->addSql('ALTER TABLE tbl_find_content_website ADD thumbnail_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_find_content_website ADD CONSTRAINT FK_3AD8D527FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_3AD8D527FDFF2E92 ON tbl_find_content_website (thumbnail_id)');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral DROP FOREIGN KEY FK_4E5A35681FDCE57C');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral DROP FOREIGN KEY FK_4E5A35683CCAA4B7');
        $this->addSql('DROP INDEX idx_4e5a35681fdce57c ON tbl_wonder_workshop_referral');
        $this->addSql('CREATE INDEX IDX_7C8048E61FDCE57C ON tbl_wonder_workshop_referral (workshop_id)');
        $this->addSql('DROP INDEX uniq_4e5a35683ccaa4b7 ON tbl_wonder_workshop_referral');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C8048E63CCAA4B7 ON tbl_wonder_workshop_referral (referral_id)');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral ADD CONSTRAINT FK_4E5A35681FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral ADD CONSTRAINT FK_4E5A35683CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_wonder_referal_referral (workshop_id INT NOT NULL, referral_id INT NOT NULL, UNIQUE INDEX UNIQ_4E5A35683CCAA4B7 (referral_id), INDEX IDX_4E5A35681FDCE57C (workshop_id), INDEX IDX_4E5A35683CCAA4B7 (referral_id), PRIMARY KEY(workshop_id, referral_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_comment_picture DROP FOREIGN KEY FK_44BD49B7F8697D13');
        $this->addSql('ALTER TABLE tbl_core_comment_picture DROP FOREIGN KEY FK_44BD49B7EE45BDBF');
        $this->addSql('ALTER TABLE tbl_find_content_video DROP FOREIGN KEY FK_BA2E7756FDFF2E92');
        $this->addSql('DROP INDEX IDX_BA2E7756FDFF2E92 ON tbl_find_content_video');
        $this->addSql('ALTER TABLE tbl_find_content_video DROP thumbnail_id');
        $this->addSql('ALTER TABLE tbl_find_content_website DROP FOREIGN KEY FK_3AD8D527FDFF2E92');
        $this->addSql('DROP INDEX IDX_3AD8D527FDFF2E92 ON tbl_find_content_website');
        $this->addSql('ALTER TABLE tbl_find_content_website DROP thumbnail_id');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral DROP FOREIGN KEY FK_7C8048E61FDCE57C');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral DROP FOREIGN KEY FK_7C8048E63CCAA4B7');
        $this->addSql('DROP INDEX uniq_7c8048e63ccaa4b7 ON tbl_wonder_workshop_referral');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E5A35683CCAA4B7 ON tbl_wonder_workshop_referral (referral_id)');
        $this->addSql('DROP INDEX idx_7c8048e61fdce57c ON tbl_wonder_workshop_referral');
        $this->addSql('CREATE INDEX IDX_4E5A35681FDCE57C ON tbl_wonder_workshop_referral (workshop_id)');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral ADD CONSTRAINT FK_7C8048E61FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_referral ADD CONSTRAINT FK_7C8048E63CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)');
    }
}
