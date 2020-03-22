<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200322123637 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity_feedback (id INT NOT NULL, feedback_id INT NOT NULL, INDEX IDX_33FBF92ED249A887 (feedback_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity_feedback ADD CONSTRAINT FK_33FBF92ED249A887 FOREIGN KEY (feedback_id) REFERENCES tbl_core_feedback (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_feedback ADD CONSTRAINT FK_33FBF92EBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_activity_feedback');
    }
}
