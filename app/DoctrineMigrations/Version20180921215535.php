<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180921215535 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX ec_event_domains ON event_category');
        $this->addSql('ALTER TABLE event_category ADD ec_category_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX ec_event_domains ON event_category (ec_event_id, ec_category_id, ec_domain)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX ec_event_domains ON event_category');
        $this->addSql('ALTER TABLE event_category DROP ec_category_id');
        $this->addSql('CREATE UNIQUE INDEX ec_event_domains ON event_category (ec_event_id, ec_title, ec_domain)');
    }
}
