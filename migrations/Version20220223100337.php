<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220223100337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_user_action ADD updated_date DATETIME NOT NULL, CHANGE date date_created DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_user_action ADD date DATETIME NOT NULL, DROP date_created, DROP updated_date, CHANGE username username VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE doi_deposit_fix doi_deposit_fix VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE doi_deposit_version doi_deposit_version VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE action action VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE zenodo_title zenodo_title VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE uid uid VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
