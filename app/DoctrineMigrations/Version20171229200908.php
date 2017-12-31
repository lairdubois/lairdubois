<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171229200908 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tbl_core_user_meta_skill (usermeta_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_B709F0ECA59D5A47 (usermeta_id), INDEX IDX_B709F0EC5585C142 (skill_id), PRIMARY KEY(usermeta_id, skill_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_howto_workflow (howto_id INT NOT NULL, workflow_id INT NOT NULL, INDEX IDX_43BE7371FBE2D86A (howto_id), INDEX IDX_43BE73712C7C2CBA (workflow_id), PRIMARY KEY(howto_id, workflow_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_creation_workflow (creation_id INT NOT NULL, workflow_id INT NOT NULL, INDEX IDX_33933E2734FFA69A (creation_id), INDEX IDX_33933E272C7C2CBA (workflow_id), PRIMARY KEY(creation_id, workflow_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_wonder_workshop_workflow (workshop_id INT NOT NULL, workflow_id INT NOT NULL, INDEX IDX_6A424DF01FDCE57C (workshop_id), INDEX IDX_6A424DF02C7C2CBA (workflow_id), PRIMARY KEY(workshop_id, workflow_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_label (id INT AUTO_INCREMENT NOT NULL, workflow_id INT NOT NULL, name VARCHAR(40) NOT NULL, color VARCHAR(7) NOT NULL, INDEX IDX_28226D312C7C2CBA (workflow_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_part (id INT AUTO_INCREMENT NOT NULL, workflow_id INT NOT NULL, number VARCHAR(10) NOT NULL, name VARCHAR(40) NOT NULL, count INT NOT NULL, INDEX IDX_150205F62C7C2CBA (workflow_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_task (id INT AUTO_INCREMENT NOT NULL, workflow_id INT NOT NULL, created_at DATETIME NOT NULL, title VARCHAR(100) NOT NULL, position_left INT NOT NULL, position_top INT NOT NULL, status SMALLINT NOT NULL, started_at DATETIME DEFAULT NULL, last_running_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, estimated_duration INT NOT NULL, duration INT NOT NULL, part_count INT NOT NULL, INDEX IDX_E73AE152C7C2CBA (workflow_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_task_label (task_id INT NOT NULL, label_id INT NOT NULL, INDEX IDX_6B872CD38DB60186 (task_id), INDEX IDX_6B872CD333B92F39 (label_id), PRIMARY KEY(task_id, label_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_task_part (task_id INT NOT NULL, part_id INT NOT NULL, INDEX IDX_D9AA8D9E8DB60186 (task_id), INDEX IDX_D9AA8D9E4CE34BEC (part_id), PRIMARY KEY(task_id, part_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_task_connection (from_task_id INT NOT NULL, to_task_id INT NOT NULL, INDEX IDX_FE283CD7BE8E229 (from_task_id), INDEX IDX_FE283CD732E3C73 (to_task_id), PRIMARY KEY(from_task_id, to_task_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow (id INT AUTO_INCREMENT NOT NULL, main_picture_id INT DEFAULT NULL, license_id INT DEFAULT NULL, user_id INT NOT NULL, title VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, body LONGTEXT DEFAULT NULL, html_body LONGTEXT DEFAULT NULL, estimated_duration INT NOT NULL, duration INT NOT NULL, task_count INT NOT NULL, running_task_count INT NOT NULL, done_task_count INT NOT NULL, creation_count INT NOT NULL, plan_count INT NOT NULL, workshop_count INT NOT NULL, howto_count INT NOT NULL, rebound_count INT NOT NULL, inspiration_count INT NOT NULL, like_count INT NOT NULL, watch_count INT NOT NULL, comment_count INT NOT NULL, view_count INT NOT NULL, visibility INT NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_32F67E3B989D9B62 (slug), INDEX IDX_32F67E3BD6BDC9DC (main_picture_id), UNIQUE INDEX UNIQ_32F67E3B460F904B (license_id), INDEX IDX_32F67E3BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_plan (workflow_id INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_81572E4D2C7C2CBA (workflow_id), INDEX IDX_81572E4DE899029B (plan_id), PRIMARY KEY(workflow_id, plan_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_inspiration (workflow_id INT NOT NULL, rebound_workflow_id INT NOT NULL, INDEX IDX_4BCB70A82C7C2CBA (workflow_id), INDEX IDX_4BCB70A870F2BC06 (rebound_workflow_id), PRIMARY KEY(workflow_id, rebound_workflow_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tbl_workflow_tag (workflow_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_BD9683382C7C2CBA (workflow_id), INDEX IDX_BD968338BAD26311 (tag_id), PRIMARY KEY(workflow_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tbl_core_user_meta_skill ADD CONSTRAINT FK_B709F0ECA59D5A47 FOREIGN KEY (usermeta_id) REFERENCES tbl_core_user_meta (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_core_user_meta_skill ADD CONSTRAINT FK_B709F0EC5585C142 FOREIGN KEY (skill_id) REFERENCES tbl_input_skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_workflow ADD CONSTRAINT FK_43BE7371FBE2D86A FOREIGN KEY (howto_id) REFERENCES tbl_howto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_howto_workflow ADD CONSTRAINT FK_43BE73712C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_workflow ADD CONSTRAINT FK_33933E2734FFA69A FOREIGN KEY (creation_id) REFERENCES tbl_wonder_creation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_creation_workflow ADD CONSTRAINT FK_33933E272C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_workflow ADD CONSTRAINT FK_6A424DF01FDCE57C FOREIGN KEY (workshop_id) REFERENCES tbl_wonder_workshop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_workflow ADD CONSTRAINT FK_6A424DF02C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_label ADD CONSTRAINT FK_28226D312C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_part ADD CONSTRAINT FK_150205F62C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_task ADD CONSTRAINT FK_E73AE152C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_task_label ADD CONSTRAINT FK_6B872CD38DB60186 FOREIGN KEY (task_id) REFERENCES tbl_workflow_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_task_label ADD CONSTRAINT FK_6B872CD333B92F39 FOREIGN KEY (label_id) REFERENCES tbl_workflow_label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_task_part ADD CONSTRAINT FK_D9AA8D9E8DB60186 FOREIGN KEY (task_id) REFERENCES tbl_workflow_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_task_part ADD CONSTRAINT FK_D9AA8D9E4CE34BEC FOREIGN KEY (part_id) REFERENCES tbl_workflow_part (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_task_connection ADD CONSTRAINT FK_FE283CD7BE8E229 FOREIGN KEY (from_task_id) REFERENCES tbl_workflow_task (id)');
        $this->addSql('ALTER TABLE tbl_workflow_task_connection ADD CONSTRAINT FK_FE283CD732E3C73 FOREIGN KEY (to_task_id) REFERENCES tbl_workflow_task (id)');
        $this->addSql('ALTER TABLE tbl_workflow ADD CONSTRAINT FK_32F67E3BD6BDC9DC FOREIGN KEY (main_picture_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_workflow ADD CONSTRAINT FK_32F67E3B460F904B FOREIGN KEY (license_id) REFERENCES tbl_core_license (id)');
        $this->addSql('ALTER TABLE tbl_workflow ADD CONSTRAINT FK_32F67E3BA76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('ALTER TABLE tbl_workflow_plan ADD CONSTRAINT FK_81572E4D2C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_plan ADD CONSTRAINT FK_81572E4DE899029B FOREIGN KEY (plan_id) REFERENCES tbl_wonder_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_4BCB70A82C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration ADD CONSTRAINT FK_4BCB70A870F2BC06 FOREIGN KEY (rebound_workflow_id) REFERENCES tbl_workflow (id)');
        $this->addSql('ALTER TABLE tbl_workflow_tag ADD CONSTRAINT FK_BD9683382C7C2CBA FOREIGN KEY (workflow_id) REFERENCES tbl_workflow (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_workflow_tag ADD CONSTRAINT FK_BD968338BAD26311 FOREIGN KEY (tag_id) REFERENCES tbl_core_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tbl_blog_post ADD bodyExtract VARCHAR(255) NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user DROP INDEX UNIQ_FAFE7AEF39FCA6F9, ADD INDEX IDX_FAFE7AEF39FCA6F9 (meta_id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP FOREIGN KEY FK_75D82335A76ED395');
        $this->addSql('DROP INDEX UNIQ_75D82335A76ED395 ON tbl_core_user_meta');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD banner_id INT DEFAULT NULL, ADD biography_id INT DEFAULT NULL, ADD website VARCHAR(255) DEFAULT NULL, ADD facebook VARCHAR(50) DEFAULT NULL, ADD twitter VARCHAR(50) DEFAULT NULL, ADD googleplus VARCHAR(50) DEFAULT NULL, ADD youtube VARCHAR(50) DEFAULT NULL, ADD vimeo VARCHAR(24) DEFAULT NULL, ADD dailymotion VARCHAR(24) DEFAULT NULL, ADD pinterest VARCHAR(50) DEFAULT NULL, ADD instagram VARCHAR(50) DEFAULT NULL, ADD auto_watch_enabled TINYINT(1) DEFAULT NULL, ADD incoming_message_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_follower_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_like_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_vote_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_following_post_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_watch_activity_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD new_spotlight_email_notification_enabled TINYINT(1) DEFAULT NULL, ADD week_news_email_enabled TINYINT(1) DEFAULT NULL, ADD follower_count INT DEFAULT NULL, ADD following_count INT DEFAULT NULL, ADD recieved_like_count INT DEFAULT NULL, ADD sent_like_count INT DEFAULT NULL, ADD positive_vote_count INT DEFAULT NULL, ADD negative_vote_count INT DEFAULT NULL, ADD unread_message_count INT DEFAULT NULL, ADD fresh_notification_count INT DEFAULT NULL, ADD comment_count INT DEFAULT NULL, ADD contribution_count INT DEFAULT NULL, ADD private_creation_count INT DEFAULT NULL, ADD public_creation_count INT DEFAULT NULL, ADD private_plan_count INT DEFAULT NULL, ADD public_plan_count INT DEFAULT NULL, ADD private_howto_count INT DEFAULT NULL, ADD public_howto_count INT DEFAULT NULL, ADD private_workshop_count INT DEFAULT NULL, ADD public_workshop_count INT DEFAULT NULL, ADD private_find_count INT DEFAULT NULL, ADD public_find_count INT DEFAULT NULL, ADD private_question_count INT DEFAULT NULL, ADD public_question_count INT DEFAULT NULL, ADD answer_count INT DEFAULT NULL, ADD private_graphic_count INT DEFAULT NULL, ADD public_graphic_count INT DEFAULT NULL, ADD private_workflow_count INT DEFAULT NULL, ADD public_workflow_count INT DEFAULT NULL, ADD proposal_count INT DEFAULT NULL, ADD testimonial_count INT DEFAULT NULL, ADD unlisted_workflow_workflow_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP COLUMN user_id, DROP COLUMN education_count');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD CONSTRAINT FK_75D82335684EC833 FOREIGN KEY (banner_id) REFERENCES tbl_core_picture (id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD CONSTRAINT FK_75D8233562283C10 FOREIGN KEY (biography_id) REFERENCES tbl_core_biography (id)');
        $this->addSql('CREATE INDEX IDX_75D82335684EC833 ON tbl_core_user_meta (banner_id)');
        $this->addSql('CREATE INDEX IDX_75D8233562283C10 ON tbl_core_user_meta (biography_id)');
        $this->addSql('ALTER TABLE tbl_faq_question ADD bodyExtract VARCHAR(255) NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_find ADD bodyExtract VARCHAR(255) NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto_article ADD bodyExtract VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tbl_howto ADD workflow_count INT NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider DROP is_draft');
        $this->addSql('ALTER TABLE tbl_knowledge2_school DROP is_draft');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood DROP is_draft');
        $this->addSql('ALTER TABLE tbl_promotion_graphic ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_answer ADD bodyExtract VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tbl_qa_question ADD bodyExtract VARCHAR(255) NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_creation ADD bodyExtract VARCHAR(255) NOT NULL, ADD workflow_count INT NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_plan ADD workflow_count INT NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_wonder_workshop ADD bodyExtract VARCHAR(255) NOT NULL, ADD workflow_count INT NOT NULL, ADD visibility INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD visibility INT NOT NULL, DROP is_draft');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_workflow_task_label DROP FOREIGN KEY FK_6B872CD333B92F39');
        $this->addSql('ALTER TABLE tbl_workflow_task_part DROP FOREIGN KEY FK_D9AA8D9E4CE34BEC');
        $this->addSql('ALTER TABLE tbl_workflow_task_label DROP FOREIGN KEY FK_6B872CD38DB60186');
        $this->addSql('ALTER TABLE tbl_workflow_task_part DROP FOREIGN KEY FK_D9AA8D9E8DB60186');
        $this->addSql('ALTER TABLE tbl_workflow_task_connection DROP FOREIGN KEY FK_FE283CD7BE8E229');
        $this->addSql('ALTER TABLE tbl_workflow_task_connection DROP FOREIGN KEY FK_FE283CD732E3C73');
        $this->addSql('ALTER TABLE tbl_howto_workflow DROP FOREIGN KEY FK_43BE73712C7C2CBA');
        $this->addSql('ALTER TABLE tbl_wonder_creation_workflow DROP FOREIGN KEY FK_33933E272C7C2CBA');
        $this->addSql('ALTER TABLE tbl_wonder_workshop_workflow DROP FOREIGN KEY FK_6A424DF02C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_label DROP FOREIGN KEY FK_28226D312C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_part DROP FOREIGN KEY FK_150205F62C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_task DROP FOREIGN KEY FK_E73AE152C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_plan DROP FOREIGN KEY FK_81572E4D2C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_4BCB70A82C7C2CBA');
        $this->addSql('ALTER TABLE tbl_workflow_inspiration DROP FOREIGN KEY FK_4BCB70A870F2BC06');
        $this->addSql('ALTER TABLE tbl_workflow_tag DROP FOREIGN KEY FK_BD9683382C7C2CBA');
        $this->addSql('DROP TABLE tbl_core_user_meta_skill');
        $this->addSql('DROP TABLE tbl_howto_workflow');
        $this->addSql('DROP TABLE tbl_wonder_creation_workflow');
        $this->addSql('DROP TABLE tbl_wonder_workshop_workflow');
        $this->addSql('DROP TABLE tbl_workflow_label');
        $this->addSql('DROP TABLE tbl_workflow_part');
        $this->addSql('DROP TABLE tbl_workflow_task');
        $this->addSql('DROP TABLE tbl_workflow_task_label');
        $this->addSql('DROP TABLE tbl_workflow_task_part');
        $this->addSql('DROP TABLE tbl_workflow_task_connection');
        $this->addSql('DROP TABLE tbl_workflow');
        $this->addSql('DROP TABLE tbl_workflow_plan');
        $this->addSql('DROP TABLE tbl_workflow_inspiration');
        $this->addSql('DROP TABLE tbl_workflow_tag');
        $this->addSql('ALTER TABLE tbl_blog_post DROP bodyExtract, DROP visibility');
        $this->addSql('ALTER TABLE tbl_core_user DROP INDEX IDX_FAFE7AEF39FCA6F9, ADD UNIQUE INDEX UNIQ_FAFE7AEF39FCA6F9 (meta_id)');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP FOREIGN KEY FK_75D82335684EC833');
        $this->addSql('ALTER TABLE tbl_core_user_meta DROP FOREIGN KEY FK_75D8233562283C10');
        $this->addSql('DROP INDEX IDX_75D82335684EC833 ON tbl_core_user_meta');
        $this->addSql('DROP INDEX IDX_75D8233562283C10 ON tbl_core_user_meta');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD user_id INT DEFAULT NULL, DROP banner_id, DROP biography_id, DROP website, DROP facebook, DROP twitter, DROP googleplus, DROP youtube, DROP vimeo, DROP dailymotion, DROP pinterest, DROP instagram, DROP auto_watch_enabled, DROP incoming_message_email_notification_enabled, DROP new_follower_email_notification_enabled, DROP new_like_email_notification_enabled, DROP new_vote_email_notification_enabled, DROP new_following_post_email_notification_enabled, DROP new_watch_activity_email_notification_enabled, DROP new_spotlight_email_notification_enabled, DROP week_news_email_enabled, DROP follower_count, DROP following_count, DROP recieved_like_count, DROP sent_like_count, DROP positive_vote_count, DROP negative_vote_count, DROP unread_message_count, DROP fresh_notification_count, DROP comment_count, DROP contribution_count, DROP private_creation_count, DROP public_creation_count, DROP private_plan_count, DROP public_plan_count, DROP private_howto_count, DROP public_howto_count, DROP private_workshop_count, DROP public_workshop_count, DROP private_find_count, DROP public_find_count, DROP private_question_count, DROP public_question_count, DROP answer_count, DROP private_graphic_count, DROP public_graphic_count, DROP private_workflow_count, DROP public_workflow_count, DROP proposal_count, DROP testimonial_count, CHANGE unlisted_workflow_workflow_count education_count INT NOT NULL');
        $this->addSql('ALTER TABLE tbl_core_user_meta ADD CONSTRAINT FK_75D82335A76ED395 FOREIGN KEY (user_id) REFERENCES tbl_core_user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_75D82335A76ED395 ON tbl_core_user_meta (user_id)');
        $this->addSql('ALTER TABLE tbl_faq_question DROP bodyExtract, DROP visibility');
        $this->addSql('ALTER TABLE tbl_find DROP bodyExtract, DROP visibility');
        $this->addSql('ALTER TABLE tbl_howto DROP workflow_count, DROP visibility');
        $this->addSql('ALTER TABLE tbl_howto_article DROP bodyExtract');
        $this->addSql('ALTER TABLE tbl_knowledge2_provider ADD is_draft TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_school ADD is_draft TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_knowledge2_wood ADD is_draft TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE tbl_promotion_graphic DROP visibility');
        $this->addSql('ALTER TABLE tbl_qa_answer DROP bodyExtract');
        $this->addSql('ALTER TABLE tbl_qa_question DROP bodyExtract, DROP visibility');
        $this->addSql('ALTER TABLE tbl_wonder_creation DROP bodyExtract, DROP workflow_count, DROP visibility');
        $this->addSql('ALTER TABLE tbl_wonder_plan DROP workflow_count, DROP visibility');
        $this->addSql('ALTER TABLE tbl_wonder_workshop DROP bodyExtract, DROP workflow_count, DROP visibility');
        $this->addSql('ALTER TABLE tbl_youtook_took ADD is_draft TINYINT(1) NOT NULL, DROP visibility');
    }
}
