<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210123100032 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta ADD creations_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD questions_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD plans_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD howtos_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD workshops_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD woods_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD books_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD softwares_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD collections_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD providers_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD schools_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD finds_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD events_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD offers_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD workflows_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE auto_watch_enabled auto_watch_enabled TINYINT(1) DEFAULT \'1\', CHANGE incoming_message_email_notification_enabled incoming_message_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_follower_email_notification_enabled new_follower_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_like_email_notification_enabled new_like_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_vote_email_notification_enabled new_vote_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_following_post_email_notification_enabled new_following_post_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_watch_activity_email_notification_enabled new_watch_activity_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_spotlight_email_notification_enabled new_spotlight_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE week_news_email_enabled week_news_email_enabled TINYINT(1) DEFAULT \'1\', CHANGE new_mention_email_notification_enabled new_mention_email_notification_enabled TINYINT(1) DEFAULT \'1\', CHANGE request_enabled request_enabled TINYINT(1) DEFAULT \'1\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta DROP creations_badge_enabled, DROP questions_badge_enabled, DROP plans_badge_enabled, DROP howtos_badge_enabled, DROP workshops_badge_enabled, DROP woods_badge_enabled, DROP books_badge_enabled, DROP softwares_badge_enabled, DROP collections_badge_enabled, DROP providers_badge_enabled, DROP schools_badge_enabled, DROP finds_badge_enabled, DROP events_badge_enabled, DROP offers_badge_enabled, DROP workflows_badge_enabled, CHANGE request_enabled request_enabled TINYINT(1) DEFAULT NULL, CHANGE auto_watch_enabled auto_watch_enabled TINYINT(1) DEFAULT NULL, CHANGE incoming_message_email_notification_enabled incoming_message_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_follower_email_notification_enabled new_follower_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_mention_email_notification_enabled new_mention_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_like_email_notification_enabled new_like_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_vote_email_notification_enabled new_vote_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_following_post_email_notification_enabled new_following_post_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_watch_activity_email_notification_enabled new_watch_activity_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE new_spotlight_email_notification_enabled new_spotlight_email_notification_enabled TINYINT(1) DEFAULT NULL, CHANGE week_news_email_enabled week_news_email_enabled TINYINT(1) DEFAULT NULL');
    }
}
