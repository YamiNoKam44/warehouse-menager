<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tworzy tabele użytkowników i artykułów.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE identity_users (
                id INT AUTO_INCREMENT NOT NULL,
                login VARCHAR(180) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL,
                UNIQUE INDEX UNIQ_FED8EF19AA08CB10 (login),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE articles (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(160) NOT NULL,
                unit_of_measure VARCHAR(32) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE identity_users');
    }
}
