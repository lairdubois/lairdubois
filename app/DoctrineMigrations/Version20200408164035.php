<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408164035 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_book_value_identity (book_id INT NOT NULL, bookidentity_id INT NOT NULL, INDEX IDX_8BA5330F16A2B381 (book_id), INDEX IDX_8BA5330F3D86E63 (bookidentity_id), PRIMARY KEY(book_id, bookidentity_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_book_identity (id INT NOT NULL, data VARCHAR(100) NOT NULL, work VARCHAR(100) NOT NULL, is_volume TINYINT(1) NOT NULL, volume VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_identity ADD CONSTRAINT FK_8BA5330F16A2B381 FOREIGN KEY (book_id) REFERENCES tbl_knowledge2_book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_identity ADD CONSTRAINT FK_8BA5330F3D86E63 FOREIGN KEY (bookidentity_id) REFERENCES tbl_knowledge2_value_book_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_book_identity ADD CONSTRAINT FK_ADC3CD42BF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD work VARCHAR(100) DEFAULT NULL, ADD is_volume TINYINT(1) NOT NULL, ADD volume VARCHAR(100) DEFAULT NULL, ADD identity VARCHAR(100) DEFAULT NULL, ADD identity_rejected TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_book_value_identity DROP FOREIGN KEY FK_8BA5330F3D86E63');
        $this->addSql('DROP TABLE tbl_knowledge2_book_value_identity');
        $this->addSql('DROP TABLE tbl_knowledge2_value_book_identity');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP work, DROP is_volume, DROP volume, DROP identity, DROP identity_rejected');
    }
}
