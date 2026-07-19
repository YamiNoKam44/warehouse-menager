<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Http\Form;

use App\Warehouse\Domain\Model\Warehouse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class WarehouseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'help' => sprintf(
                    'Od %d do %d znaków.',
                    Warehouse::NAME_MIN_LENGTH,
                    Warehouse::NAME_MAX_LENGTH,
                ),
                'help_attr' => ['class' => 'field-hint'],
                'attr' => [
                    'minlength' => Warehouse::NAME_MIN_LENGTH,
                    'maxlength' => Warehouse::NAME_MAX_LENGTH,
                    'autofocus' => true,
                ],
                'constraints' => [
                    new NotBlank(message: 'Nazwa magazynu jest wymagana.'),
                    new Length(
                        min: Warehouse::NAME_MIN_LENGTH,
                        max: Warehouse::NAME_MAX_LENGTH,
                        minMessage: 'Nazwa magazynu musi mieć co najmniej {{ limit }} znaki.',
                        maxMessage: 'Nazwa magazynu może mieć maksymalnie {{ limit }} znaków.',
                    ),
                ],
            ])
            ->add('assignedUsers', UserAutocompleteField::class, [
                'label' => 'Przypisani użytkownicy',
                'help' => 'Wyszukaj i wybierz osoby, które mają widzieć ten magazyn.',
                'help_attr' => ['class' => 'field-hint'],
                'multiple' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WarehouseFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'warehouse';
    }
}
