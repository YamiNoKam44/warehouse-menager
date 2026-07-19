<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Form;

use App\Warehouse\Domain\Model\Warehouse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
final class WarehouseAutocompleteField extends AbstractType
{
    public const int MAX_RESULTS = 5;
    public const int MIN_CHARACTERS = 0;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Warehouse::class,
            'choice_label' => static fn (Warehouse $warehouse): string => $warehouse->name(),
            'searchable_fields' => ['name'],
            'max_results' => self::MAX_RESULTS,
            'min_characters' => self::MIN_CHARACTERS,
            'preload' => 'focus',
            'loading_more_text' => 'Wczytywanie kolejnych wyników...',
            'no_results_found_text' => 'Nie znaleziono magazynu',
            'no_more_results_text' => 'Brak kolejnych wyników',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
