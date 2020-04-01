<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200401151158 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_wood_value_database_link (wood_id INT NOT NULL, url_id INT NOT NULL, INDEX IDX_2BAE77DC7B2710BE (wood_id), INDEX IDX_2BAE77DC81CFDAE7 (url_id), PRIMARY KEY(wood_id, url_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood_value_database_link ADD CONSTRAINT FK_2BAE77DC7B2710BE FOREIGN KEY (wood_id) REFERENCES tbl_knowledge2_wood (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood_value_database_link ADD CONSTRAINT FK_2BAE77DC81CFDAE7 FOREIGN KEY (url_id) REFERENCES tbl_knowledge2_value_url (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD databaseLink LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_knowledge2_wood_value_database_link');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP databaseLink');
    }
}
