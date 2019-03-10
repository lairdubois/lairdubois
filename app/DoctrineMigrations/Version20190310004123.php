<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190310004123 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_find ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD collection_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_find DROP collection_count');
        $this->addSql('ALTER TABLE tbl_howto DROP collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_book DROP collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP collection_count');
        $this->addSql('ALTER TABLE tbl_promotion_graphic DROP collection_count');
        $this->addSql('ALTER TABLE tbl_qa_question DROP collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP collection_count');
    }
}
