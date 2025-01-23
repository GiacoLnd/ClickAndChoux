<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122224318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD token VARCHAR(64) DEFAULT NULL, CHANGE date_livraison date_livraison DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE image image VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP roles');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP token, CHANGE date_livraison date_livraison DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD roles VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE image image VARCHAR(255) DEFAULT NULL');
    }
}
