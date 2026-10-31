<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Author;
use App\Entity\Book;
use App\Enum\BookCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Book>
 */
final class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ['label' => 'app.ui.title', 'help' => 'app.ui.title_help'])
            ->add('authorName', null, ['label' => 'app.ui.author_name'])
            ->add('author', EntityType::class, [
                'label' => 'app.ui.author',
                'class' => Author::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'app.ui.choose_author',
            ])
            ->add('category', EnumType::class, [
                'label' => 'app.ui.category',
                'class' => BookCategory::class,
                'choice_label' => static fn (BookCategory $choice): string => ucfirst($choice->value),
                'required' => false,
                'placeholder' => 'app.ui.choose_category',
            ])
            ->add('summary', TextareaType::class, ['label' => 'app.ui.summary', 'required' => false])
            ->add('available', CheckboxType::class, ['label' => 'app.ui.available', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Book::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_resource';
    }
}
