<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191111083318 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_knowledge2_school_value_video (school_id INT NOT NULL, video_id INT NOT NULL, INDEX IDX_606EF390C32A47EE (school_id), INDEX IDX_606EF39029C1004E (video_id), PRIMARY KEY(school_id, video_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_knowledge2_value_video (id INT NOT NULL, data VARCHAR(255) NOT NULL, kind SMALLINT NOT NULL, embedIdentifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_video ADD CONSTRAINT FK_606EF390C32A47EE FOREIGN KEY (school_id) REFERENCES tbl_knowledge2_school (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_video ADD CONSTRAINT FK_606EF39029C1004E FOREIGN KEY (video_id) REFERENCES tbl_knowledge2_value_video (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_value_video ADD CONSTRAINT FK_7CFA52AFBF396750 FOREIGN KEY (id) REFERENCES tbl_knowledge2_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD video VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_knowledge2_school_value_video DROP FOREIGN KEY FK_606EF39029C1004E');
        $this->addSql('DROP TABLE tbl_knowledge2_school_value_video');
        $this->addSql('DROP TABLE tbl_knowledge2_value_video');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP video');
    }
}
