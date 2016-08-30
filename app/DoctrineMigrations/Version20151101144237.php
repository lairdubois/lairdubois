<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151101144237 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_find_content_event (id INT NOT NULL, location VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, start_date DATE NOT NULL, start_time TIME DEFAULT NULL, end_date DATE DEFAULT NULL, end_time TIME DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_find_content_event_picture (event_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_DF7E8EE171F7E88B (event_id), INDEX IDX_DF7E8EE1EE45BDBF (picture_id), PRIMARY KEY(event_id, picture_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_find_content_event ADD CONSTRAINT FK_FD47A7DDBF396750 FOREIGN KEY (id) REFERENCES tbl_find_content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_find_content_event_picture ADD CONSTRAINT FK_DF7E8EE171F7E88B FOREIGN KEY (event_id) REFERENCES tbl_find_content_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_find_content_event_picture ADD CONSTRAINT FK_DF7E8EE1EE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_find_content_event_picture DROP FOREIGN KEY FK_DF7E8EE171F7E88B');
        $this->addSql('DROP TABLE tbl_find_content_event');
        $this->addSql('DROP TABLE tbl_find_content_event_picture');
    }
}
