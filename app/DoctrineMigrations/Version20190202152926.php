<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190202152926 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_book_review');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_review (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(100) NOT NULL COLLATE utf8_unicode_ci, rating INT DEFAULT NULL, body LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, html_body LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, INDEX IDX_AF0F87E3A76ED395 (user_id), INDEX IDX_AF0F87E316A2B381 (book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_review ADD CONSTRAINT FK_AF0F87E316A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id)');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_review ADD CONSTRAINT FK_AF0F87E3A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
    }
}
