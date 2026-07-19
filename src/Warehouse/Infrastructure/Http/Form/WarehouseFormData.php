<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Http\Form;

use App\Identity\Domain\Model\User;
use App\Warehouse\Application\Dto\AssignedUserIds;
use App\Warehouse\Application\Dto\WarehouseData;
use App\Warehouse\Domain\Model\Warehouse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class WarehouseFormData
{
    /** @var Collection<int, User> */
    private Collection $assignedUsers;

    /**
     * @param iterable<User>|null $assignedUsers
     */
    public function __construct(
        private string $name = '',
        ?iterable $assignedUsers = null,
    ) {
        $this->assignedUsers = new ArrayCollection();

        if (null !== $assignedUsers) {
            $this->setAssignedUsers($assignedUsers);
        }
    }

    public static function fromWarehouse(Warehouse $warehouse): self
    {
        return new self($warehouse->name(), $warehouse->assignedUsers());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @return Collection<int, User> */
    public function getAssignedUsers(): Collection
    {
        return $this->assignedUsers;
    }

    /**
     * @param iterable<User> $assignedUsers
     */
    public function setAssignedUsers(iterable $assignedUsers): void
    {
        $this->assignedUsers->clear();

        foreach ($assignedUsers as $user) {
            if (!$this->assignedUsers->contains($user)) {
                $this->assignedUsers->add($user);
            }
        }
    }

    public function toWarehouseData(): WarehouseData
    {
        return new WarehouseData(
            $this->name,
            AssignedUserIds::fromUsers($this->assignedUsers),
        );
    }
}
