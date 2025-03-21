<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250321074214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD nom_livraison VARCHAR(255) DEFAULT NULL, ADD prenom_livraison VARCHAR(255) DEFAULT NULL, ADD nom_facturation VARCHAR(255) DEFAULT NULL, ADD prenom_facturation VARCHAR(255) DEFAULT NULL, ADD adresse_facturation VARCHAR(255) DEFAULT NULL, ADD code_postal_facturation VARCHAR(255) DEFAULT NULL, ADD ville_facturation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD is_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP nom_livraison, DROP prenom_livraison, DROP nom_facturation, DROP prenom_facturation, DROP adresse_facturation, DROP code_postal_facturation, DROP ville_facturation');
        $this->addSql('ALTER TABLE produit DROP is_active');
    }
}
