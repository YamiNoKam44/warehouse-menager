<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http\Form;

use App\Article\Domain\Model\Article;
use App\Stock\Application\Dto\ReceiptDocumentUpload;
use App\Stock\Domain\Model\ReceiptDocument;
use App\Stock\Domain\Model\ReceiptDocumentType;
use App\Stock\Domain\Model\StockQuantity;
use App\Stock\Domain\Model\StockReceipt;
use App\Stock\Domain\Model\VatRate;
use App\Warehouse\Domain\Model\Warehouse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

final class StockReceiptType extends AbstractType
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
                'help' => 'Jednostka miary pochodzi z definicji artykułu i jest widoczna przy jego nazwie.',
                'help_attr' => ['class' => 'field-hint'],
                'constraints' => [
                    new NotNull(message: 'Wybierz artykuł.'),
                ],
            ])
            ->add('quantity', TextType::class, [
                'label' => 'Ilość przyjęta',
                'help' => 'Maksymalnie 3 miejsca po przecinku.',
                'help_attr' => ['class' => 'field-hint'],
                'attr' => [
                    'inputmode' => 'decimal',
                    'placeholder' => 'np. 12,500',
                ],
                'constraints' => [
                    new NotBlank(message: 'Ilość przyjęta jest wymagana.'),
                    new Regex(
                        pattern: StockQuantity::INPUT_PATTERN,
                        message: 'Podaj dodatnią liczbę z maksymalnie 3 miejscami po przecinku.',
                    ),
                ],
            ])
            ->add('vatRate', EnumType::class, [
                'class' => VatRate::class,
                'choice_label' => static fn (VatRate $vatRate): string => sprintf('%d%%', $vatRate->value),
                'placeholder' => 'Wybierz stawkę VAT',
                'label' => 'VAT',
                'invalid_message' => 'Wybierz jedną z dostępnych stawek VAT.',
                'constraints' => [
                    new NotNull(message: 'Wybierz stawkę VAT.'),
                ],
            ])
            ->add('unitNetPrice', TextType::class, [
                'label' => 'Cena jednostkowa netto',
                'help' => 'Kwota bez VAT, maksymalnie 2 miejsca po przecinku.',
                'help_attr' => ['class' => 'field-hint'],
                'attr' => [
                    'inputmode' => 'decimal',
                    'placeholder' => 'np. 19,99',
                ],
                'constraints' => [
                    new NotBlank(message: 'Cena jednostkowa netto jest wymagana.'),
                    new Regex(
                        pattern: StockReceipt::UNIT_NET_PRICE_INPUT_PATTERN,
                        message: 'Podaj nieujemną cenę z maksymalnie 2 miejscami po przecinku.',
                    ),
                ],
            ])
            ->add('documents', FileType::class, [
                'label' => 'Faktury',
                'help' => sprintf(
                    'Opcjonalnie: maksymalnie %d pliki PDF lub XML, do 10 MiB każdy.',
                    StockReceipt::MAX_DOCUMENTS,
                ),
                'help_attr' => ['class' => 'field-hint'],
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'accept' => '.pdf,.xml,application/pdf,application/xml,text/xml',
                ],
                'constraints' => [
                    new Count(
                        max: StockReceipt::MAX_DOCUMENTS,
                        maxMessage: 'Możesz dołączyć maksymalnie {{ limit }} pliki.',
                    ),
                    new All(new File(
                        maxSize: ReceiptDocumentUpload::MAX_FILE_SIZE_BYTES,
                        extensions: [
                            ReceiptDocumentType::PDF->value,
                            ReceiptDocumentType::XML->value,
                        ],
                        filenameMaxLength: ReceiptDocument::ORIGINAL_NAME_MAX_LENGTH,
                        maxSizeMessage: 'Plik jest za duży. Maksymalny rozmiar to {{ limit }} {{ suffix }}.',
                        extensionsMessage: 'Dozwolone są wyłącznie pliki PDF lub XML.',
                        disallowEmptyMessage: 'Pusty plik nie może zostać dołączony.',
                    )),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => StockReceiptFormData::class,
            ])
            ->setRequired(['warehouses', 'articles'])
            ->setAllowedTypes('warehouses', 'iterable')
            ->setAllowedTypes('articles', 'iterable');
    }

    public function getBlockPrefix(): string
    {
        return 'stock_receipt';
    }
}
