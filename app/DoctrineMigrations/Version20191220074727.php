<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191220074727 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_faq_question ADD main_picture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_faq_question ADD CONSTRAINT FK_B406CACFD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_B406CACFD6BDC9DC ON tbl_faq_question (main_picture_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_faq_question DROP FOREIGN KEY FK_B406CACFD6BDC9DC');
        $this->addSql('DROP INDEX IDX_B406CACFD6BDC9DC ON tbl_faq_question');
        $this->addSql('ALTER TABLE tbl_faq_question DROP main_picture_id');
    }
}
