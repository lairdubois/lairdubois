<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200116102909 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post DROP publishCount');
        $this->addSql('ALTER TABLE tbl_faq_question DROP publishCount');
        $this->addSql('ALTER TABLE tbl_find DROP publishCount');
        $this->addSql('ALTER TABLE tbl_howto DROP publishCount');
        $this->addSql('ALTER TABLE tbl_promotion_graphic DROP publishCount');
        $this->addSql('ALTER TABLE tbl_qa_question DROP publishCount');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP publishCount');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP publishCount');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP publishCount');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_blog_post ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_faq_question ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_find ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD publishCount INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD publishCount INT NOT NULL');
    }
}
