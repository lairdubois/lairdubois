<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140215092136 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_knowledge_wood_contributor (wood_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_10A6E3477B2710BE (wood_id), INDEX IDX_10A6E347A76ED395 (user_id), PRIMARY KEY(wood_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_knowledge_wood_contributor ADD CONSTRAINT FK_10A6E3477B2710BE FOREIGN KEY (wood_id) REFERENCES tbl_knowledge_wood (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_knowledge_wood_contributor ADD CONSTRAINT FK_10A6E347A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE tbl_knowledge_wood_contributor");
    }
}
