<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140428223527 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_creation ADD sticker_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_creation ADD CONSTRAINT FK_DDB282FC4D965A4D FOREIGN KEY (sticker_id) REFERENCES tbl_core_picture (id)");
        $this->addSql("CREATE INDEX IDX_DDB282FC4D965A4D ON tbl_wonder_creation (sticker_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_creation DROP FOREIGN KEY FK_DDB282FC4D965A4D");
        $this->addSql("DROP INDEX IDX_DDB282FC4D965A4D ON tbl_wonder_creation");
        $this->addSql("ALTER TABLE tbl_wonder_creation DROP sticker_id");
    }
}
