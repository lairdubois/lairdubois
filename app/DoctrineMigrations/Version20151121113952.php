<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151121113952 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_wonder_howto_referral (howto_id INT NOT NULL, referral_id INT NOT NULL, INDEX IDX_EF0D5963FBE2D86A (howto_id), UNIQUE INDEX UNIQ_EF0D59633CCAA4B7 (referral_id), PRIMARY KEY(howto_id, referral_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_wonder_howto_referral ADD CONSTRAINT FK_EF0D5963FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_howto_referral ADD CONSTRAINT FK_EF0D59633CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)');
        $this->addSql('ALTER TABLE tbl_howto ADD sticker_id INT DEFAULT NULL, ADD referral_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD CONSTRAINT FK_65A1D8354D965A4D FOREIGN KEY (sticker_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_65A1D8354D965A4D ON tbl_howto (sticker_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_wonder_howto_referral');
        $this->addSql('ALTER TABLE tbl_howto DROP FOREIGN KEY FK_65A1D8354D965A4D');
        $this->addSql('DROP INDEX IDX_65A1D8354D965A4D ON tbl_howto');
        $this->addSql('ALTER TABLE tbl_howto DROP sticker_id, DROP referral_count');
    }
}
