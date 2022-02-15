<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class AuthorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('creator',TextType::class,['attr' => ['class'=>'col-4','placeholder' => 'Family name, given names']])
            ->add('affiliation',TextType::class, ['attr' => ['class'=>'col-4'], 'required' => false])
            ->add('orcid',TextType::class, ['attr' => ['class'=>'col-4','empty_data' => '',],'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
