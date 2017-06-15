<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170614113007 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_howto_provider (howto_id INT NOT NULL, provider_id INT NOT NULL, INDEX IDX_B4BF98FBFBE2D86A (howto_id), INDEX IDX_B4BF98FBA53A8AA (provider_id), PRIMARY KEY(howto_id, provider_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_creation_provider (creation_id INT NOT NULL, provider_id INT NOT NULL, INDEX IDX_C492D5AD34FFA69A (creation_id), INDEX IDX_C492D5ADA53A8AA (provider_id), PRIMARY KEY(creation_id, provider_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_howto_provider ADD CONSTRAINT FK_B4BF98FBFBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_provider ADD CONSTRAINT FK_B4BF98FBA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_provider ADD CONSTRAINT FK_C492D5AD34FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_provider ADD CONSTRAINT FK_C492D5ADA53A8AA FOREIGN KEY (provider_id) REFERENCES tbl_knowledge2_provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto ADD provider_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD creation_count INT NOT NULL, ADD howto_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD provider_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_howto_provider');
        $this->addSql('DROP TABLE tbl_wonder_creation_provider');
        $this->addSql('ALTER TABLE tbl_howto DROP provider_count');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP creation_count, DROP howto_count');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP provider_count');
    }
}
