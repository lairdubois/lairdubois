<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191219183742 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_offer_picture (offer_id INT NOT NULL, picture_id INT NOT NULL, INDEX IDX_AE41EC8B53C674EE (offer_id), INDEX IDX_AE41EC8BEE45BDBF (picture_id), PRIMARY KEY(offer_id, picture_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_offer_picture ADD CONSTRAINT FK_AE41EC8B53C674EE FOREIGN KEY (offer_id) REFERENCES tbl_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_offer_picture ADD CONSTRAINT FK_AE41EC8BEE45BDBF FOREIGN KEY (picture_id) REFERENCES tbl_core_picture (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_offer_picture');
    }
}
