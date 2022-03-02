<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220223093636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, uid VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX idx_log_unique ON log_user_action (username, doi_deposit_fix, doi_deposit_version, action)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP INDEX idx_log_unique ON log_user_action');
        $this->addSql('ALTER TABLE log_user_action CHANGE username username VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE doi_deposit_fix doi_deposit_fix VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE doi_deposit_version doi_deposit_version VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE action action VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE zenodo_title zenodo_title VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
