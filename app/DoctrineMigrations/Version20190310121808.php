<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190310121808 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_find ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_workflow ADD public_collection_count INT NOT NULL, CHANGE collection_count private_collection_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_find ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_howto ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_book ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_qa_question ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
        $this->addSql('ALTER TABLE tbl_workflow ADD collection_count INT NOT NULL, DROP private_collection_count, DROP public_collection_count');
    }
}
