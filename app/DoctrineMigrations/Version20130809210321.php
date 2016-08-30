<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130809210321 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_wonder_workshop ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL");
        $this->addSql("ALTER TABLE tbl_core_user ADD facebook VARCHAR(255) DEFAULT NULL, ADD twitter VARCHAR(255) DEFAULT NULL, ADD googleplus VARCHAR(255) DEFAULT NULL, ADD youtube VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_user DROP facebook, DROP twitter, DROP googleplus, DROP youtube");
        $this->addSql("ALTER TABLE tbl_wonder_workshop DROP latitude, DROP longitude");
    }
}
