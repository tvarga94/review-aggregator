<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Cégnév',
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Értékelés',
                'choices' => [
                    '5 - Kiváló' => 5,
                    '4 - Jó' => 4,
                    '3 - Közepes' => 3,
                    '2 - Gyenge' => 2,
                    '1 - Rossz' => 1,
                ],
                'placeholder' => 'Válassz értékelést',
            ])
            ->add('reviewText', TextareaType::class, [
                'label' => 'Vélemény szövege',
            ])
            ->add('authorEmail', EmailType::class, [
                'label' => 'Email cím',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Vélemény beküldése',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
