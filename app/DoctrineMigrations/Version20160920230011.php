<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160920230011 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_funding_charge (id INT AUTO_INCREMENT NOT NULL, funding_id INT DEFAULT NULL, amount INT NOT NULL, type SMALLINT NOT NULL, INDEX IDX_E69323699D70482 (funding_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_funding_donation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hashid VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, amount INT NOT NULL, fee INT NOT NULL, stripe_charge_id VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_4D2C07C2EAA56FDE (stripe_charge_id), INDEX IDX_4D2C07C2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_funding (id INT AUTO_INCREMENT NOT NULL, month SMALLINT NOT NULL, year SMALLINT NOT NULL, charge_balance INT NOT NULL, carried_forward_balance INT NOT NULL, donation_balance INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_funding_charge ADD CONSTRAINT FK_E69323699D70482 FOREIGN KEY (funding_id) REFERENCES tbl_funding (id)');
        $this->addSql('ALTER TABLE tbl_funding_donation ADD CONSTRAINT FK_4D2C07C2A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_user CHANGE username username VARCHAR(180) NOT NULL, CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE email_canonical email_canonical VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAFE7AEFC05FB297 ON tbl_core_user (confirmation_token)');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD donation_count INT NOT NULL, ADD donation_balance INT NOT NULL, ADD donation_fee_balance INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_funding_charge DROP FOREIGN KEY FK_E69323699D70482');
        $this->addSql('DROP TABLE tbl_funding_charge');
        $this->addSql('DROP TABLE tbl_funding_donation');
        $this->addSql('DROP TABLE tbl_funding');
        $this->addSql('DROP INDEX UNIQ_FAFE7AEFC05FB297 ON tbl_core_user');
        $this->addSql('ALTER TABLE tbl_core_user CHANGE username username VARCHAR(255) NOT NULL, CHANGE username_canonical username_canonical VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE email_canonical email_canonical VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP donation_count, DROP donation_balance, DROP donation_fee_balance');
    }
}
