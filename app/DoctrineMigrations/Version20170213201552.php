<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170213201552 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_youtube_video (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, embedIdentifier VARCHAR(255) NOT NULL, thumbnail_loc VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TINYTEXT NOT NULL, channel_id VARCHAR(255) NOT NULL, channel_thumbnail_loc VARCHAR(255) NOT NULL, channel_title VARCHAR(255) NOT NULL, channel_description LONGTEXT NOT NULL, INDEX IDX_B7DFA8C2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_youtube_video ADD CONSTRAINT FK_B7DFA8C2A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_message_picture DROP FOREIGN KEY FK_274611AB537A1329');
        $this->addSql('ALTER TABLE tbl_message_picture DROP FOREIGN KEY FK_274611ABEE45BDBF');
        $this->addSql('DROP INDEX idx_274611ab537a1329 ON tbl_message_picture');
        $this->addSql('CREATE INDEX IDX_587F200B537A1329 ON tbl_message_picture (message_id)');
        $this->addSql('DROP INDEX idx_274611abee45bdbf ON tbl_message_picture');
        $this->addSql('CREATE INDEX IDX_587F200BEE45BDBF ON tbl_message_picture (picture_id)');
        $this->addSql('ALTER TABLE tbl_message_picture ADD CONSTRAINT FK_274611AB537A1329 FOREIGN KEY (message_id) REFERENCES tbl_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_message_picture ADD CONSTRAINT FK_274611ABEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user DROP locked, DROP expired, DROP expires_at, DROP credentials_expired, DROP credentials_expire_at, CHANGE salt salt VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_youtube_video');
        $this->addSql('ALTER TABLE tbl_core_user ADD locked TINYINT(1) NOT NULL, ADD expired TINYINT(1) NOT NULL, ADD expires_at DATETIME DEFAULT NULL, ADD credentials_expired TINYINT(1) NOT NULL, ADD credentials_expire_at DATETIME DEFAULT NULL, CHANGE salt salt VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE tbl_message_picture DROP FOREIGN KEY FK_587F200B537A1329');
        $this->addSql('ALTER TABLE tbl_message_picture DROP FOREIGN KEY FK_587F200BEE45BDBF');
        $this->addSql('DROP INDEX idx_587f200b537a1329 ON tbl_message_picture');
        $this->addSql('CREATE INDEX IDX_274611AB537A1329 ON tbl_message_picture (message_id)');
        $this->addSql('DROP INDEX idx_587f200bee45bdbf ON tbl_message_picture');
        $this->addSql('CREATE INDEX IDX_274611ABEE45BDBF ON tbl_message_picture (picture_id)');
        $this->addSql('ALTER TABLE tbl_message_picture ADD CONSTRAINT FK_587F200B537A1329 FOREIGN KEY (message_id) REFERENCES tbl_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_message_picture ADD CONSTRAINT FK_587F200BEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
    }
}
