<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180425215645 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_review (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(100) NOT NULL, rating INT DEFAULT NULL, body LONGTEXT DEFAULT NULL, html_body LONGTEXT DEFAULT NULL, INDEX IDX_AF0F87E316A2B381 (book_id), INDEX IDX_AF0F87E3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_review ADD CONSTRAINT FK_AF0F87E316A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_review ADD CONSTRAINT FK_AF0F87E3A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD review_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD review_count INT NOT NULL, ADD average_rating DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_book_review');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP review_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP review_count, DROP average_rating');
    }
}
