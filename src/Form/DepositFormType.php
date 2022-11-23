<?php

namespace App\Form;

use App\Service\EpisciencesClient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;


class DepositFormType extends AbstractType
{
    /**
     * @var mixed
     */
    private $publicationDate;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (isset($options['publicationDate'])){
            $this->publicationDate = $options['publicationDate'];
        }
        $builder
            ->add('depositFile',FileType::class,[
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'multiple' => 'multiple'
                ]
            ])
            ->add('title')
            ->add('upload_type',ChoiceType::class, [
                'choices' => [
                    'Publication' => 'publication',
                    'Dataset' => 'dataset',
                ],
                'expanded' => true,

            ])
            ->add('publication_type',ChoiceType::class, [
                'choices' => [
                    //'Annotation collection' => 'annotationcollection',
                    //'Book' => 'book',
                    //'Book section' => 'section',
                    'Conference paper' => 'conferencepaper',
                    //'Data management plan' => 'datamanagementplan',
                    'Journal article' => 'article',
                    //'Patent' => 'patent',
                    'Preprint' => 'preprint',
                    //'Project deliverable' => 'deliverable',
                    //'Project milestone' => 'milestone',
                    //'Proposal' => 'proposal',
                    //'Report' => 'report',
                    //'Software documentation' => 'softwaredocumentation',
                    //'Taxonomic treatment' => 'taxonomictreatment',
                    //'Technical note' => 'technicalnote',
                    //'Thesis' => 'thesis',
                    'Working paper' => 'workingpaper',
                    'Other' => 'other'
                ],
                'expanded' => false,

            ])
            ->add('description',TextareaType::class, [
                'attr' => ['class' => 'tinymce','rows' => 10],
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'label' => 'Publication date',
                'data' => $this->publicationDate
            ])
            ->add('author',CollectionType::class,[
                'entry_type' => AuthorFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'attr' => ['class'=>'row'],
                'row_attr' => ['class' => 'row'],
                'entry_options'  => [
                    'attr' => ['class'=>'row'],
                    'label' => false
                ],
                'prototype_data' => [
                    'attr' => ['class'=>'col-4'],
                ],

            ])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-outline-success w-100 mb-3 mt-3'],'label' => 'Save as draft'])
            ->add('save_publish', SubmitType::class, ['attr' => ['class' => 'btn btn-outline-success w-100 mb-3 mt-3'], 'label' => 'Save and Publish'])
            ->add('new_version', SubmitType::class,  ['attr' => ['class' => 'btn btn-outline-success w-100 mb-3 mt-3'], 'label' => 'New Version'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'publicationDate' => new \DateTime()
        ]);
    }
}
