<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180427055208 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_public_domain (book_id INT NOT NULL, integer_id INT NOT NULL, INDEX IDX_137A16BE16A2B381 (book_id), INDEX IDX_137A16BEB7585238 (integer_id), PRIMARY KEY(book_id, integer_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_public_domain ADD CONSTRAINT FK_137A16BE16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_public_domain ADD CONSTRAINT FK_137A16BEB7585238 FOREIGN KEY (integer_id) REFERENCES tbl_knowledge2_value_integer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD publicDomain TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_book_value_public_domain');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP publicDomain');
    }
}
