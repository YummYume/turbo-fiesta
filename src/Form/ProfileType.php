<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Profile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'profile.username',
                'required' => true,
                'help' => 'profile.username.help',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'profile.description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('categories', EntityType::class, [
                'label' => 'user.categories',
                'help' => 'user.categories.help',
                'class' => Category::class,
                'choice_label' => 'name',
                'translation_domain' => 'tables',
                'multiple' => true,
                'autocomplete' => true,
            ])
            ->add('picture', ProfilePictureType::class, [
                'label' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
