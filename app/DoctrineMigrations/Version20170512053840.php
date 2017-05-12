<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170512053840 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD logo_rejected TINYINT(1) NOT NULL, CHANGE signrejected sign_rejected TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD grain_rejected TINYINT(1) NOT NULL, CHANGE namerejected name_rejected TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_youtook_took CHANGE channel_thumbnail_url channel_thumbnail_url VARCHAR(255) NOT NULL, CHANGE thumbnail_loc thumbnail_loc VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD signRejected TINYINT(1) NOT NULL, DROP sign_rejected, DROP logo_rejected');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD nameRejected TINYINT(1) NOT NULL, DROP name_rejected, DROP grain_rejected');
        $this->addSql('ALTER TABLE tbl_youtook_took CHANGE thumbnail_loc thumbnail_loc VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE utf8_unicode_ci, CHANGE channel_thumbnail_url channel_thumbnail_url VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE utf8_unicode_ci');
    }
}
