<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Form;

use App\Identity\Application\Dto\AssignedWarehouseIds;
use App\Identity\Application\Dto\PlainPassword;
use App\Identity\Application\Dto\UserData;
use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Model\Warehouse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class UserFormData
{
    /** @var Collection<int, Warehouse> */
    private Collection $assignedWarehouses;

    /** @param iterable<Warehouse>|null $assignedWarehouses */
    public function __construct(
        private string $login = '',
        private ?string $plainPassword = null,
        ?iterable $assignedWarehouses = null,
    ) {
        $this->assignedWarehouses = new ArrayCollection();

        if (null !== $assignedWarehouses) {
            $this->setAssignedWarehouses($assignedWarehouses);
        }
    }

    /** @param iterable<Warehouse> $assignedWarehouses */
    public static function fromUser(User $user, iterable $assignedWarehouses): self
    {
        return new self($user->login(), null, $assignedWarehouses);
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /** @return Collection<int, Warehouse> */
    public function getAssignedWarehouses(): Collection
    {
        return $this->assignedWarehouses;
    }

    /** @param iterable<Warehouse> $assignedWarehouses */
    public function setAssignedWarehouses(iterable $assignedWarehouses): void
    {
        $this->assignedWarehouses->clear();

        foreach ($assignedWarehouses as $warehouse) {
            if (!$this->assignedWarehouses->contains($warehouse)) {
                $this->assignedWarehouses->add($warehouse);
            }
        }
    }

    public function toUserData(): UserData
    {
        $password = null;

        if (null !== $this->plainPassword && '' !== $this->plainPassword) {
            $password = PlainPassword::fromString($this->plainPassword);
        }

        return new UserData(
            $this->login,
            $password,
            AssignedWarehouseIds::fromWarehouses($this->assignedWarehouses),
        );
    }
}
