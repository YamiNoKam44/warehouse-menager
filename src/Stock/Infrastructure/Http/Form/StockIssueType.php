<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http\Form;

use App\Article\Domain\Model\Article;
use App\Stock\Domain\Model\StockQuantity;
use App\Warehouse\Domain\Model\Warehouse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

final class StockIssueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('warehouse', EntityType::class, [
                'class' => Warehouse::class,
                'choices' => $options['warehouses'],
                'choice_label' => static fn (Warehouse $warehouse): string => $warehouse->name(),
                'placeholder' => 'Wybierz magazyn',
                'label' => 'Magazyn',
                'constraints' => [
                    new NotNull(message: 'Wybierz magazyn.'),
                ],
            ])
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choices' => $options['articles'],
                'choice_label' => static fn (Article $article): string => sprintf(
                    '%s — jednostka: %s',
                    $article->name(),
                    $article->unitOfMeasure(),
                ),
                'placeholder' => 'Wybierz artykuł',
                'label' => 'Artykuł',
                'help' => 'Jednostka miary pochodzi z definicji artykułu.',
                'help_attr' => ['class' => 'field-hint'],
                'constraints' => [
                    new NotNull(message: 'Wybierz artykuł.'),
                ],
            ])
            ->add('quantity', TextType::class, [
                'label' => 'Ilość wydana',
                'help' => 'Maksymalnie 3 miejsca po przecinku.',
                'help_attr' => ['class' => 'field-hint'],
                'attr' => [
                    'inputmode' => 'decimal',
                    'placeholder' => 'np. 2,500',
                ],
                'constraints' => [
                    new NotBlank(message: 'Ilość wydana jest wymagana.'),
                    new Regex(
                        pattern: StockQuantity::INPUT_PATTERN,
                        message: 'Podaj dodatnią liczbę z maksymalnie 3 miejscami po przecinku.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => StockIssueFormData::class,
            ])
            ->setRequired(['warehouses', 'articles'])
            ->setAllowedTypes('warehouses', 'iterable')
            ->setAllowedTypes('articles', 'iterable');
    }

    public function getBlockPrefix(): string
    {
        return 'stock_issue';
    }
}
