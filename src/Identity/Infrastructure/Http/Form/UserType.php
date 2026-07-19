<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Form;

use App\Identity\Application\Dto\PlainPassword;
use App\Identity\Domain\Model\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordRequired = true === $options['password_required'];
        $passwordConstraints = [
            new Length(
                min: PlainPassword::MIN_LENGTH,
                max: PlainPassword::MAX_LENGTH,
                minMessage: 'Hasło musi mieć co najmniej {{ limit }} znaków.',
                maxMessage: 'Hasło może mieć maksymalnie {{ limit }} znaków.',
            ),
        ];

        if ($passwordRequired) {
            $passwordConstraints[] = new NotBlank(message: 'Hasło jest wymagane.');
        }

        $builder
            ->add('login', TextType::class, [
                'label' => 'Login',
                'help' => sprintf(
                    'Od %d do %d znaków: małe litery, cyfry, kropka, myślnik lub podkreślenie.',
                    User::LOGIN_MIN_LENGTH,
                    User::LOGIN_MAX_LENGTH,
                ),
                'help_attr' => ['class' => 'field-hint'],
                'attr' => [
                    'minlength' => User::LOGIN_MIN_LENGTH,
                    'maxlength' => User::LOGIN_MAX_LENGTH,
                    'autofocus' => true,
                    'autocomplete' => 'username',
                ],
                'constraints' => [
                    new NotBlank(message: 'Login jest wymagany.'),
                    new Length(
                        min: User::LOGIN_MIN_LENGTH,
                        max: User::LOGIN_MAX_LENGTH,
                        minMessage: 'Login musi mieć co najmniej {{ limit }} znaki.',
                        maxMessage: 'Login może mieć maksymalnie {{ limit }} znaków.',
                    ),
                    new Regex(
                        pattern: User::LOGIN_PATTERN,
                        message: 'Login ma nieprawidłowy format.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Hasło',
                'help' => $passwordRequired
                    ? sprintf('Od %d do %d znaków.', PlainPassword::MIN_LENGTH, PlainPassword::MAX_LENGTH)
                    : 'Pozostaw puste, aby zachować obecne hasło.',
                'help_attr' => ['class' => 'field-hint'],
                'required' => $passwordRequired,
                'empty_data' => null,
                'trim' => false,
                'attr' => [
                    'minlength' => PlainPassword::MIN_LENGTH,
                    'maxlength' => PlainPassword::MAX_LENGTH,
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $passwordConstraints,
            ])
            ->add('assignedWarehouses', WarehouseAutocompleteField::class, [
                'label' => 'Przypisane magazyny',
                'help' => 'Wyszukaj i wybierz magazyny dostępne dla użytkownika.',
                'help_attr' => ['class' => 'field-hint'],
                'multiple' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => UserFormData::class,
                'password_required' => true,
            ])
            ->setAllowedTypes('password_required', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'user';
    }
}
