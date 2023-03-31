<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Entity\Content;
use App\Form\Type\EditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
              'label' => 'content.title',
            ])
            ->add('blocks', EditorType::class, [
              'label' => false,
            ])
            ->add('published', CheckboxType::class, [
              'required' => false,
              'label' => 'content.published',
            ])
            ->add('categories', EntityType::class, [
              'label' => 'content.categories',
              'class' => Category::class,
              'choice_label' => 'name',
              'translation_domain' => 'tables',
              'multiple' => true,
              'autocomplete' => true,
              'required' => true,
          ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
          'data_class' => Content::class,
        ]);
    }
}
