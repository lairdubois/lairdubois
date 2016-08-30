<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140501154757 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tbl_core_referer_door (id INT AUTO_INCREMENT NOT NULL, referer_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, path VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_2B68AEA687C61384 (referer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_referer (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, baseUrl VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_core_referer_referral (id INT AUTO_INCREMENT NOT NULL, referer_id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, access_count INT NOT NULL, INDEX IDX_DEF02F4487C61384 (referer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_creation_referral (creation_id INT NOT NULL, referral_id INT NOT NULL, INDEX IDX_25513B3134FFA69A (creation_id), UNIQUE INDEX UNIQ_25513B313CCAA4B7 (referral_id), PRIMARY KEY(creation_id, referral_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_plan_referral (plan_id INT NOT NULL, referral_id INT NOT NULL, INDEX IDX_C5E9CE85E899029B (plan_id), UNIQUE INDEX UNIQ_C5E9CE853CCAA4B7 (referral_id), PRIMARY KEY(plan_id, referral_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE tbl_wonder_referal_referral (workshop_id INT NOT NULL, referral_id INT NOT NULL, INDEX IDX_4E5A35681FDCE57C (workshop_id), UNIQUE INDEX UNIQ_4E5A35683CCAA4B7 (referral_id), PRIMARY KEY(workshop_id, referral_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE tbl_core_referer_door ADD CONSTRAINT FK_2B68AEA687C61384 FOREIGN KEY (referer_id) REFERENCES tbl_core_referer (id)");
        $this->addSql("ALTER TABLE tbl_core_referer_referral ADD CONSTRAINT FK_DEF02F4487C61384 FOREIGN KEY (referer_id) REFERENCES tbl_core_referer (id)");
        $this->addSql("ALTER TABLE tbl_wonder_creation_referral ADD CONSTRAINT FK_25513B3134FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_creation_referral ADD CONSTRAINT FK_25513B313CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)");
        $this->addSql("ALTER TABLE tbl_wonder_plan_referral ADD CONSTRAINT FK_C5E9CE85E899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_plan_referral ADD CONSTRAINT FK_C5E9CE853CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)");
        $this->addSql("ALTER TABLE tbl_wonder_referal_referral ADD CONSTRAINT FK_4E5A35681FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tbl_wonder_referal_referral ADD CONSTRAINT FK_4E5A35683CCAA4B7 FOREIGN KEY (referral_id) REFERENCES tbl_core_referer_referral (id)");
        $this->addSql("ALTER TABLE tbl_wonder_creation ADD referral_count INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_plan ADD referral_count INT NOT NULL");
        $this->addSql("ALTER TABLE tbl_wonder_workshop ADD referral_count INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE tbl_core_referer_door DROP FOREIGN KEY FK_2B68AEA687C61384");
        $this->addSql("ALTER TABLE tbl_core_referer_referral DROP FOREIGN KEY FK_DEF02F4487C61384");
        $this->addSql("ALTER TABLE tbl_wonder_creation_referral DROP FOREIGN KEY FK_25513B313CCAA4B7");
        $this->addSql("ALTER TABLE tbl_wonder_plan_referral DROP FOREIGN KEY FK_C5E9CE853CCAA4B7");
        $this->addSql("ALTER TABLE tbl_wonder_referal_referral DROP FOREIGN KEY FK_4E5A35683CCAA4B7");
        $this->addSql("DROP TABLE tbl_core_referer_door");
        $this->addSql("DROP TABLE tbl_core_referer");
        $this->addSql("DROP TABLE tbl_core_referer_referral");
        $this->addSql("DROP TABLE tbl_wonder_creation_referral");
        $this->addSql("DROP TABLE tbl_wonder_plan_referral");
        $this->addSql("DROP TABLE tbl_wonder_referal_referral");
        $this->addSql("ALTER TABLE tbl_wonder_creation DROP referral_count");
        $this->addSql("ALTER TABLE tbl_wonder_plan DROP referral_count");
        $this->addSql("ALTER TABLE tbl_wonder_workshop DROP referral_count");
    }
}
