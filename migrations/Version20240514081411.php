<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240514081411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entree DROP FOREIGN KEY FK_598377A6670C757F');
        $this->addSql('DROP INDEX IDX_598377A6670C757F ON entree');
        $this->addSql('ALTER TABLE entree DROP fournisseur_id, DROP action, DROP nom_produit');
        $this->addSql('ALTER TABLE produit DROP nombre, DROP nom_produit_detail, DROP prix_detail, DROP qt_stock_detail, DROP nbre_vendu');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entree ADD fournisseur_id INT DEFAULT NULL, ADD action VARCHAR(255) DEFAULT NULL, ADD nom_produit VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE entree ADD CONSTRAINT FK_598377A6670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('CREATE INDEX IDX_598377A6670C757F ON entree (fournisseur_id)');
        $this->addSql('ALTER TABLE produit ADD nombre DOUBLE PRECISION DEFAULT NULL, ADD nom_produit_detail VARCHAR(255) DEFAULT NULL, ADD prix_detail DOUBLE PRECISION DEFAULT NULL, ADD qt_stock_detail DOUBLE PRECISION DEFAULT NULL, ADD nbre_vendu DOUBLE PRECISION DEFAULT NULL');
    }
}
