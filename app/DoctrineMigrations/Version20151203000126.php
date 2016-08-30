<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151203000126 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_howto_article ADD sticker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_howto_article ADD CONSTRAINT FK_A0E1C5564D965A4D FOREIGN KEY (sticker_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_A0E1C5564D965A4D ON tbl_howto_article (sticker_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_howto_article DROP FOREIGN KEY FK_A0E1C5564D965A4D');
        $this->addSql('DROP INDEX IDX_A0E1C5564D965A4D ON tbl_howto_article');
        $this->addSql('ALTER TABLE tbl_howto_article DROP sticker_id');
    }
}
