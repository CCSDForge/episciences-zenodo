<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class EpisciencesFormType extends AbstractType
{

    /**
     * @var mixed
     */
    private $journals;
    /**
     * @var mixed
     */
    private $doi;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->journals = $options['journals'];
        $this->doi = $options['doi'];
        $doiSend = explode('/',$this->doi)['1'];
        $builder
            ->add('episcienceslink_journals', ChoiceType::class, ['choices' => $options['journals'], 'label' => false,'placeholder' => 'Select journal'])
            ->add('confirm', SubmitType::class, ['attr' => ['class' => 'btn btn-outline-success w-100 mb-3','id'=> 'submit-epi-link-btn']])
            ->add('doi_show',HiddenType::class,[
                'data'=>$this->doi,
            ])
            ->add('id',HiddenType::class,[
                'data'=> $doiSend,
            ])
            ->add('repoid',HiddenType::class,['attr'=> ['value'=>'4']]) // 4 because zenodo
            ->add('ci',HiddenType::class,['data'=>$options['ci']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'journals' => null,
            'doi'=> null,
            'ci' => null
        ]);
    }
}
