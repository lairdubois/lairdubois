<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190202103121 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_review (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(100) NOT NULL, rating INT DEFAULT NULL, body LONGTEXT DEFAULT NULL, html_body LONGTEXT DEFAULT NULL, INDEX IDX_5E7E0207A76ED395 (user_id), INDEX IDX_REVIEW_ENTITY (entity_type, entity_id), UNIQUE INDEX ENTITY_USER_UNIQUE (entity_type, entity_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_review ADD CONSTRAINT FK_5E7E0207A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
		$this->addSql('ALTER TABLE tbl_knowledge2_provider ADD review_count INT NOT NULL, ADD average_rating DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_review DROP FOREIGN KEY FK_8A2005E83E2E969B');
        $this->addSql('DROP TABLE tbl_core_review');
		$this->addSql('ALTER TABLE tbl_knowledge2_provider DROP review_count, DROP average_rating');
    }
}
