<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240523162619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dette_fournisseur (id INT AUTO_INCREMENT NOT NULL, fournisseur_id INT DEFAULT NULL, montant_dette VARCHAR(255) NOT NULL, montant_avance VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, reste VARCHAR(255) DEFAULT NULL, date DATETIME NOT NULL, INDEX IDX_F5D38592670C757F (fournisseur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fournisseur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payoff_supplier (id INT AUTO_INCREMENT NOT NULL, fournisseur_id INT DEFAULT NULL, dette_fournisseur_id INT DEFAULT NULL, montant VARCHAR(255) NOT NULL, date_paiement DATETIME NOT NULL, reste VARCHAR(255) NOT NULL, INDEX IDX_908127FD670C757F (fournisseur_id), INDEX IDX_908127FDC1F8463B (dette_fournisseur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dette_fournisseur ADD CONSTRAINT FK_F5D38592670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE payoff_supplier ADD CONSTRAINT FK_908127FD670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE payoff_supplier ADD CONSTRAINT FK_908127FDC1F8463B FOREIGN KEY (dette_fournisseur_id) REFERENCES dette_fournisseur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_fournisseur DROP FOREIGN KEY FK_F5D38592670C757F');
        $this->addSql('ALTER TABLE payoff_supplier DROP FOREIGN KEY FK_908127FD670C757F');
        $this->addSql('ALTER TABLE payoff_supplier DROP FOREIGN KEY FK_908127FDC1F8463B');
        $this->addSql('DROP TABLE dette_fournisseur');
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('DROP TABLE payoff_supplier');
    }
}
