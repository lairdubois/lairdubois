<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140429100306 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD sticker_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD CONSTRAINT FK_22AAE7D4D965A4D FOREIGN KEY (sticker_id) REFERENCES tbl_core_picture (id)");
        $this->addSql("CREATE INDEX IDX_22AAE7D4D965A4D ON tbl_wonder_plan (sticker_id)");
        $this->addSql("ALTER TABLE tbl_wonder_workshop ADD sticker_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop ADD CONSTRAINT FK_1133054C4D965A4D FOREIGN KEY (sticker_id) REFERENCES tbl_core_picture (id)");
        $this->addSql("CREATE INDEX IDX_1133054C4D965A4D ON tbl_wonder_workshop (sticker_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP FOREIGN KEY FK_22AAE7D4D965A4D");
        $this->addSql("DROP INDEX IDX_22AAE7D4D965A4D ON tbl_wonder_plan");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP sticker_id");
        $this->addSql("ALTER TABLE tbl_wonder_workshop DROP FOREIGN KEY FK_1133054C4D965A4D");
        $this->addSql("DROP INDEX IDX_1133054C4D965A4D ON tbl_wonder_workshop");
        $this->addSql("ALTER TABLE tbl_wonder_workshop DROP sticker_id");
    }
}
