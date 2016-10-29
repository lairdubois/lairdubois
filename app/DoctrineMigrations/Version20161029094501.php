<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161029094501 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD strip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD CONSTRAINT FK_DDB282FC7F2DB86A FOREIGN KEY (strip_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_DDB282FC7F2DB86A ON tbl_wonder_creation (strip_id)');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD strip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD CONSTRAINT FK_22AAE7D7F2DB86A FOREIGN KEY (strip_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_22AAE7D7F2DB86A ON tbl_wonder_plan (strip_id)');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD strip_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD CONSTRAINT FK_1133054C7F2DB86A FOREIGN KEY (strip_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE INDEX IDX_1133054C7F2DB86A ON tbl_wonder_workshop (strip_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP FOREIGN KEY FK_DDB282FC7F2DB86A');
        $this->addSql('DROP INDEX IDX_DDB282FC7F2DB86A ON tbl_wonder_creation');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP strip_id');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP FOREIGN KEY FK_22AAE7D7F2DB86A');
        $this->addSql('DROP INDEX IDX_22AAE7D7F2DB86A ON tbl_wonder_plan');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP strip_id');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP FOREIGN KEY FK_1133054C7F2DB86A');
        $this->addSql('DROP INDEX IDX_1133054C7F2DB86A ON tbl_wonder_workshop');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP strip_id');
    }
}
