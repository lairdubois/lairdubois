<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151017173930 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_activity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, is_pending_notifications TINYINT(1) NOT NULL, discr INT NOT NULL, INDEX IDX_A241AF2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_comment (id INT NOT NULL, comment_id INT NOT NULL, INDEX IDX_48513C27F8697D13 (comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_contribute (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_follow (id INT NOT NULL, follower_id INT NOT NULL, INDEX IDX_9B57C05EAC24F853 (follower_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_like (id INT NOT NULL, like_id INT NOT NULL, INDEX IDX_39BD82D8859BFA32 (like_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_mention (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_publish (id INT NOT NULL, entity_type SMALLINT NOT NULL, entity_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_vote (id INT NOT NULL, vote_id INT NOT NULL, INDEX IDX_CFCE470F72DCDAFC (vote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_activity_write (id INT NOT NULL, message_id INT NOT NULL, INDEX IDX_A79E1C4D537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_core_notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, activity_id INT NOT NULL, created_at DATETIME NOT NULL, is_pending_email TINYINT(1) NOT NULL, is_listed TINYINT(1) NOT NULL, is_shown TINYINT(1) NOT NULL, INDEX IDX_ACE50180A76ED395 (user_id), INDEX IDX_ACE5018081C06096 (activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_activity ADD CONSTRAINT FK_A241AF2A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_comment ADD CONSTRAINT FK_48513C27F8697D13 FOREIGN KEY (comment_id) REFERENCES tbl_core_comment (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_comment ADD CONSTRAINT FK_48513C27BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_contribute ADD CONSTRAINT FK_2C252AB2BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_follow ADD CONSTRAINT FK_9B57C05EAC24F853 FOREIGN KEY (follower_id) REFERENCES tbl_core_follower (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_follow ADD CONSTRAINT FK_9B57C05EBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_like ADD CONSTRAINT FK_39BD82D8859BFA32 FOREIGN KEY (like_id) REFERENCES tbl_core_like (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_like ADD CONSTRAINT FK_39BD82D8BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_mention ADD CONSTRAINT FK_3E273786BF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_publish ADD CONSTRAINT FK_64B1A20ABF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_vote ADD CONSTRAINT FK_CFCE470F72DCDAFC FOREIGN KEY (vote_id) REFERENCES tbl_core_vote (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_vote ADD CONSTRAINT FK_CFCE470FBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_activity_write ADD CONSTRAINT FK_A79E1C4D537A1329 FOREIGN KEY (message_id) REFERENCES tbl_message (id)');
        $this->addSql('ALTER TABLE tbl_core_activity_write ADD CONSTRAINT FK_A79E1C4DBF396750 FOREIGN KEY (id) REFERENCES tbl_core_activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_notification ADD CONSTRAINT FK_ACE50180A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_core_notification ADD CONSTRAINT FK_ACE5018081C06096 FOREIGN KEY (activity_id) REFERENCES tbl_core_activity (id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD unlisted_notification_count INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_activity_comment DROP FOREIGN KEY FK_48513C27BF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_contribute DROP FOREIGN KEY FK_2C252AB2BF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_follow DROP FOREIGN KEY FK_9B57C05EBF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_like DROP FOREIGN KEY FK_39BD82D8BF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_mention DROP FOREIGN KEY FK_3E273786BF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_publish DROP FOREIGN KEY FK_64B1A20ABF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_vote DROP FOREIGN KEY FK_CFCE470FBF396750');
        $this->addSql('ALTER TABLE tbl_core_activity_write DROP FOREIGN KEY FK_A79E1C4DBF396750');
        $this->addSql('ALTER TABLE tbl_core_notification DROP FOREIGN KEY FK_ACE5018081C06096');
        $this->addSql('DROP TABLE tbl_core_activity');
        $this->addSql('DROP TABLE tbl_core_activity_comment');
        $this->addSql('DROP TABLE tbl_core_activity_contribute');
        $this->addSql('DROP TABLE tbl_core_activity_follow');
        $this->addSql('DROP TABLE tbl_core_activity_like');
        $this->addSql('DROP TABLE tbl_core_activity_mention');
        $this->addSql('DROP TABLE tbl_core_activity_publish');
        $this->addSql('DROP TABLE tbl_core_activity_vote');
        $this->addSql('DROP TABLE tbl_core_activity_write');
        $this->addSql('DROP TABLE tbl_core_notification');
        $this->addSql('ALTER TABLE tbl_core_user DROP unlisted_notification_count');
    }
}
