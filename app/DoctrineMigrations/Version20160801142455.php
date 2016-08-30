<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160801142455 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_join (id INT NOT NULL, join_id INT NOT NULL, INDEX IDX_BDBF1ABC9DD3F874 (join_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_join (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, INDEX IDX_5F0C7471A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_join ADD CONSTRAINT FK_BDBF1ABC9DD3F874 FOREIGN KEY (join_id) REFERENCES tbl_core_join (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_join ADD CONSTRAINT FK_BDBF1ABCBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_join ADD CONSTRAINT FK_5F0C7471A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_find ADD join_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_join DROP FOREIGN KEY FK_BDBF1ABC9DD3F874');
        $this->addSql('DROP TABLE tbl_core_activity_join');
        $this->addSql('DROP TABLE tbl_core_join');
        $this->addSql('ALTER TABLE tbl_find DROP join_count');
    }
}
