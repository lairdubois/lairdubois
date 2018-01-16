<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180115200525 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tbl_core_user_skill');
        $this->addSql('ALTER TABLE tbl_core_user DROP FOREIGN KEY FK_FAFE7AEF62283C10');
        $this->addSql('ALTER TABLE tbl_core_user DROP FOREIGN KEY FK_FAFE7AEF684EC833');
        $this->addSql('DROP INDEX UNIQ_FAFE7AEF62283C10 ON tbl_core_user');
        $this->addSql('DROP INDEX IDX_FAFE7AEF684EC833 ON tbl_core_user');
        $this->addSql('ALTER TABLE tbl_core_user DROP biography_id, DROP banner_id, DROP website, DROP incoming_message_email_notification_enabled, DROP new_follower_email_notification_enabled, DROP new_like_email_notification_enabled, DROP new_following_post_email_notification_enabled, DROP new_watch_activity_email_notification_enabled, DROP follower_count, DROP following_count, DROP unread_message_count, DROP contribution_count, DROP comment_count, DROP auto_watch_enabled, DROP draft_creation_count, DROP published_creation_count, DROP draft_plan_count, DROP published_plan_count, DROP draft_howto_count, DROP published_howto_count, DROP draft_workshop_count, DROP published_workshop_count, DROP published_find_count, DROP facebook, DROP twitter, DROP googleplus, DROP youtube, DROP pinterest, DROP week_news_email_enabled, DROP recieved_like_count, DROP sent_like_count, DROP draft_find_count, DROP new_vote_email_notification_enabled, DROP positive_vote_count, DROP negative_vote_count, DROP proposal_count, DROP new_spotlight_email_notification_enabled, DROP vimeo, DROP dailymotion, DROP fresh_notification_count, DROP instagram, DROP draft_question_count, DROP published_question_count, DROP answer_count, DROP testimonial_count, DROP draft_graphic_count, DROP published_graphic_count');
        $this->addSql('ALTER TABLE tbl_workflow ADD copy_count INT NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_user_skill (user_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_5E9FCE40A76ED395 (user_id), INDEX IDX_5E9FCE405585C142 (skill_id), PRIMARY KEY(user_id, skill_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_user_skill ADD CONSTRAINT FK_5E9FCE405585C142 FOREIGN KEY (skill_id) REFERENCES tbl_input_skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_skill ADD CONSTRAINT FK_5E9FCE40A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user ADD biography_id INT DEFAULT NULL, ADD banner_id INT DEFAULT NULL, ADD website VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD incoming_message_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_follower_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_like_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_following_post_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_watch_activity_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD follower_count INT DEFAULT NULL, ADD following_count INT DEFAULT NULL, ADD unread_message_count INT DEFAULT NULL, ADD contribution_count INT DEFAULT NULL, ADD comment_count INT DEFAULT NULL, ADD auto_watch_enabled TINYINT(1) DEFAULT NULL, ADD draft_creation_count INT DEFAULT NULL, ADD published_creation_count INT DEFAULT NULL, ADD draft_plan_count INT DEFAULT NULL, ADD published_plan_count INT DEFAULT NULL, ADD draft_howto_count INT DEFAULT NULL, ADD published_howto_count INT DEFAULT NULL, ADD draft_workshop_count INT DEFAULT NULL, ADD published_workshop_count INT DEFAULT NULL, ADD published_find_count INT DEFAULT NULL, ADD facebook VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD twitter VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD googleplus VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD youtube VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD pinterest VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD week_news_email_enabled TINYINT(1) DEFAULT NULL, ADD recieved_like_count INT DEFAULT NULL, ADD sent_like_count INT DEFAULT NULL, ADD draft_find_count INT DEFAULT NULL, ADD new_vote_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD positive_vote_count INT DEFAULT NULL, ADD negative_vote_count INT DEFAULT NULL, ADD proposal_count INT DEFAULT NULL, ADD new_spotlight_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD vimeo VARCHAR(24) DEFAULT NULL COLLATE utf8_unicode_ci, ADD dailymotion VARCHAR(24) DEFAULT NULL COLLATE utf8_unicode_ci, ADD fresh_notification_count INT DEFAULT NULL, ADD instagram VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD draft_question_count INT DEFAULT NULL, ADD published_question_count INT DEFAULT NULL, ADD answer_count INT DEFAULT NULL, ADD testimonial_count INT DEFAULT NULL, ADD draft_graphic_count INT DEFAULT NULL, ADD published_graphic_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tbl_core_user ADD CONSTRAINT FK_FAFE7AEF62283C10 FOREIGN KEY (biography_id) REFERENCES tbl_core_biography (id)');
        $this->addSql('ALTER TABLE tbl_core_user ADD CONSTRAINT FK_FAFE7AEF684EC833 FOREIGN KEY (banner_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAFE7AEF62283C10 ON tbl_core_user (biography_id)');
        $this->addSql('CREATE INDEX IDX_FAFE7AEF684EC833 ON tbl_core_user (banner_id)');
        $this->addSql('ALTER TABLE tbl_workflow DROP copy_count');
    }
}
