<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180428134633 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_authors (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_B04EF91A16A2B381 (book_id), INDEX IDX_B04EF91A698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_authors ADD CONSTRAINT FK_B04EF91A16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_authors ADD CONSTRAINT FK_B04EF91A698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_author');
        $this->addSql('ALTER TABLE tbl_knowledge2_book CHANGE author authors VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_author (book_id INT NOT NULL, text_id INT NOT NULL, INDEX IDX_910B482F16A2B381 (book_id), INDEX IDX_910B482F698D3548 (text_id), PRIMARY KEY(book_id, text_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_author ADD CONSTRAINT FK_910B482F16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_author ADD CONSTRAINT FK_910B482F698D3548 FOREIGN KEY (text_id) REFERENCES tbl_knowledge2_value_text (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_authors');
        $this->addSql('ALTER TABLE tbl_knowledge2_book CHANGE authors author VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
