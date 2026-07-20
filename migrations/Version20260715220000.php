<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tworzy początkowy schemat aplikacji magazynowej.';
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

        $this->addSql(
            'CREATE TABLE warehouses (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(160) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE warehouse_users (
                warehouse_id INT NOT NULL,
                user_id INT NOT NULL,
                INDEX IDX_95E2D9245080ECDE (warehouse_id),
                INDEX IDX_95E2D924A76ED395 (user_id),
                PRIMARY KEY(warehouse_id, user_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE stock_receipts (
                id INT AUTO_INCREMENT NOT NULL,
                warehouse_id INT NOT NULL,
                article_id INT NOT NULL,
                received_by_id INT NOT NULL,
                quantity NUMERIC(15, 3) NOT NULL,
                unit_of_measure VARCHAR(32) NOT NULL,
                vat_rate SMALLINT NOT NULL,
                unit_net_price NUMERIC(15, 2) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_522DB7E05080ECDE (warehouse_id),
                INDEX IDX_522DB7E07294869C (article_id),
                INDEX IDX_522DB7E06F8DDD17 (received_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE stock_receipt_documents (
                id INT AUTO_INCREMENT NOT NULL,
                receipt_id INT NOT NULL,
                stored_name VARCHAR(64) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                type VARCHAR(8) NOT NULL,
                UNIQUE INDEX UNIQ_FEF83E231185AF6A (stored_name),
                INDEX IDX_FEF83E232B5CA896 (receipt_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE stock_issues (
                id INT AUTO_INCREMENT NOT NULL,
                warehouse_id INT NOT NULL,
                article_id INT NOT NULL,
                issued_by_id INT NOT NULL,
                quantity NUMERIC(15, 3) NOT NULL,
                unit_of_measure VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_105BAD155080ECDE (warehouse_id),
                INDEX IDX_105BAD157294869C (article_id),
                INDEX IDX_105BAD15784BB717 (issued_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE warehouse_users
                ADD CONSTRAINT FK_95E2D9245080ECDE
                FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE warehouse_users
                ADD CONSTRAINT FK_95E2D924A76ED395
                FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE stock_receipts
                ADD CONSTRAINT FK_522DB7E05080ECDE
                FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)'
        );
        $this->addSql(
            'ALTER TABLE stock_receipts
                ADD CONSTRAINT FK_522DB7E07294869C
                FOREIGN KEY (article_id) REFERENCES articles (id)'
        );
        $this->addSql(
            'ALTER TABLE stock_receipts
                ADD CONSTRAINT FK_522DB7E06F8DDD17
                FOREIGN KEY (received_by_id) REFERENCES identity_users (id)'
        );
        $this->addSql(
            'ALTER TABLE stock_receipt_documents
                ADD CONSTRAINT FK_FEF83E232B5CA896
                FOREIGN KEY (receipt_id) REFERENCES stock_receipts (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE stock_issues
                ADD CONSTRAINT FK_105BAD155080ECDE
                FOREIGN KEY (warehouse_id) REFERENCES warehouses (id)'
        );
        $this->addSql(
            'ALTER TABLE stock_issues
                ADD CONSTRAINT FK_105BAD157294869C
                FOREIGN KEY (article_id) REFERENCES articles (id)'
        );
        $this->addSql(
            'ALTER TABLE stock_issues
                ADD CONSTRAINT FK_105BAD15784BB717
                FOREIGN KEY (issued_by_id) REFERENCES identity_users (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stock_receipt_documents');
        $this->addSql('DROP TABLE stock_receipts');
        $this->addSql('DROP TABLE stock_issues');
        $this->addSql('DROP TABLE warehouse_users');
        $this->addSql('DROP TABLE warehouses');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE identity_users');
    }
}
