<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170628072125 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_wonder_creation_count INT NOT NULL, ADD unlisted_wonder_plan_count INT NOT NULL, ADD unlisted_wonder_workshop_count INT NOT NULL, ADD unlisted_find_find_count INT NOT NULL, ADD unlisted_howto_howto_count INT NOT NULL, ADD unlisted_knowledge_wood_count INT NOT NULL, ADD unlisted_knowledge_provider_count INT NOT NULL, ADD unlisted_blog_post_count INT NOT NULL, ADD unlisted_faq_question_count INT NOT NULL, DROP unlisted_creation_count, DROP unlisted_plan_count, DROP unlisted_workshop_count, DROP unlisted_find_count, DROP unlisted_howto_count, DROP unlisted_wood_count, DROP unlisted_post_count, DROP unlisted_question_count, DROP unlisted_provider_count');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tbl_core_user_meta ADD unlisted_creation_count INT NOT NULL, ADD unlisted_plan_count INT NOT NULL, ADD unlisted_workshop_count INT NOT NULL, ADD unlisted_find_count INT NOT NULL, ADD unlisted_howto_count INT NOT NULL, ADD unlisted_wood_count INT NOT NULL, ADD unlisted_post_count INT NOT NULL, ADD unlisted_question_count INT NOT NULL, ADD unlisted_provider_count INT NOT NULL, DROP unlisted_wonder_creation_count, DROP unlisted_wonder_plan_count, DROP unlisted_wonder_workshop_count, DROP unlisted_find_find_count, DROP unlisted_howto_howto_count, DROP unlisted_knowledge_wood_count, DROP unlisted_knowledge_provider_count, DROP unlisted_blog_post_count, DROP unlisted_faq_question_count');
    }
}
