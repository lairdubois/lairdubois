<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141122190637 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE tbl_core_user_meta (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, unlisted_creation_count INT NOT NULL, unlisted_plan_count INT NOT NULL, unlisted_workshop_count INT NOT NULL, unlisted_find_count INT NOT NULL, unlisted_howto_count INT NOT NULL, unlisted_wood_count INT NOT NULL, unlisted_post_count INT NOT NULL, unlisted_question_count INT NOT NULL, UNIQUE INDEX UNIQ_75D82335A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_view (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, kind SMALLINT NOT NULL, INDEX IDX_89900728A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD CONSTRAINT FK_75D82335A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_view ADD CONSTRAINT FK_89900728A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD meta_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_user ADD CONSTRAINT FK_FAFE7AEF39FCA6F9 FOREIGN KEY (meta_id) REFERENCES tbl_core_user_meta (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAFE7AEF39FCA6F9 ON tbl_core_user (meta_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE tbl_core_user DROP FOREIGN KEY FK_FAFE7AEF39FCA6F9');
        $this->addSql('DROP TABLE tbl_core_user_meta');
        $this->addSql('DROP TABLE tbl_core_view');
        $this->addSql('DROP INDEX UNIQ_FAFE7AEF39FCA6F9 ON tbl_core_user');
        $this->addSql('ALTER TABLE tbl_core_user DROP meta_id');
    }
}
