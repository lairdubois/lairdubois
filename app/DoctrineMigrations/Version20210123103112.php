<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210123103112 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta ADD wonder_creations_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD qa_questions_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD wonder_plans_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD howto_howtos_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD wonder_workshops_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD knowledge_woods_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD knowledge_books_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD knowledge_softwares_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD collection_collections_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD knowledgeproviders_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD knowledge_schools_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD find_finds_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD event_events_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD offer_offers_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD workflow_workflows_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD promotion_graphics_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD blog_posts_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD faq_questions_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, DROP creations_badge_enabled, DROP questions_badge_enabled, DROP plans_badge_enabled, DROP howtos_badge_enabled, DROP workshops_badge_enabled, DROP woods_badge_enabled, DROP books_badge_enabled, DROP softwares_badge_enabled, DROP collections_badge_enabled, DROP providers_badge_enabled, DROP schools_badge_enabled, DROP finds_badge_enabled, DROP events_badge_enabled, DROP offers_badge_enabled, DROP workflows_badge_enabled');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta ADD creations_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD questions_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD plans_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD howtos_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD workshops_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD woods_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD books_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD softwares_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD collections_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD providers_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD schools_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD finds_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD events_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD offers_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD workflows_badge_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, DROP wonder_creations_badge_enabled, DROP qa_questions_badge_enabled, DROP wonder_plans_badge_enabled, DROP howto_howtos_badge_enabled, DROP wonder_workshops_badge_enabled, DROP knowledge_woods_badge_enabled, DROP knowledge_books_badge_enabled, DROP knowledge_softwares_badge_enabled, DROP collection_collections_badge_enabled, DROP knowledgeproviders_badge_enabled, DROP knowledge_schools_badge_enabled, DROP find_finds_badge_enabled, DROP event_events_badge_enabled, DROP offer_offers_badge_enabled, DROP workflow_workflows_badge_enabled, DROP promotion_graphics_badge_enabled, DROP blog_posts_badge_enabled, DROP faq_questions_badge_enabled');
    }
}
