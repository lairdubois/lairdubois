<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201216095738 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_input_hardware (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) NOT NULL, slug VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_F2237624989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_creation_hardware (creation_id INT NOT NULL, hardware_id INT NOT NULL, INDEX IDX_A8CF4FD134FFA69A (creation_id), INDEX IDX_A8CF4FD1C9CC762B (hardware_id), PRIMARY KEY(creation_id, hardware_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_wonder_creation_hardware ADD CONSTRAINT FK_A8CF4FD134FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_hardware ADD CONSTRAINT FK_A8CF4FD1C9CC762B FOREIGN KEY (hardware_id) REFERENCES tbl_input_hardware (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_wonder_creation_hardware DROP FOREIGN KEY FK_A8CF4FD1C9CC762B');
        $this->addSql('DROP TABLE tbl_input_hardware');
        $this->addSql('DROP TABLE tbl_wonder_creation_hardware');
    }
}
