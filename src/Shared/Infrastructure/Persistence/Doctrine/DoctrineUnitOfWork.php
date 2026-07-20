<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\Exception\PersistenceForeignKeyConstraintViolation;
use App\Shared\Application\Exception\PersistenceUniqueConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUnitOfWork implements UnitOfWork
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function commit(): void
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw PersistenceUniqueConstraintViolation::fromPrevious($exception);
        } catch (ForeignKeyConstraintViolationException $exception) {
            throw PersistenceForeignKeyConstraintViolation::fromPrevious($exception);
        }
    }
}
