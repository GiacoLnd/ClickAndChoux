<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123212319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter la colonne `roles` sans contrainte NOT NULL
        $this->addSql('ALTER TABLE user ADD roles JSON DEFAULT NULL');
    
        // 2. Remplir les données existantes avec une valeur par défaut
        $this->addSql('UPDATE user SET roles = \'["ROLE_USER"]\' WHERE roles IS NULL');
    
        // 3. Modifier la colonne pour la rendre NOT NULL
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    
        // 4. Gestion des autres colonnes (user_id et date_livraison)
        $this->addSql('UPDATE commande SET user_id = 0 WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE commande CHANGE user_id user_id INT NOT NULL');
    
        $this->addSql('UPDATE commande SET date_livraison = NOW() WHERE date_livraison IS NULL');
        $this->addSql('ALTER TABLE commande CHANGE date_livraison date_livraison DATETIME NOT NULL');
    }
    
    
}
