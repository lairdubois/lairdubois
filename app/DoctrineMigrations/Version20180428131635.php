<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180428131635 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_toc (book_id INT NOT NULL, longtext_id INT NOT NULL, INDEX IDX_DE24FA1816A2B381 (book_id), INDEX IDX_DE24FA18ABCBF34C (longtext_id), PRIMARY KEY(book_id, longtext_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_toc ADD CONSTRAINT FK_DE24FA1816A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_toc ADD CONSTRAINT FK_DE24FA18ABCBF34C FOREIGN KEY (longtext_id) REFERENCES tbl_knowledge2_value_longtext (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD toc LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_book_value_toc');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP toc');
    }
}
