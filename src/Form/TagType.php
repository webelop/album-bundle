<?php

namespace Webelop\AlbumBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('hash')
            ->add('slug')
            //->add('cover')
            ->add('class')
            ->add(
                'sort',
                ChoiceType::class,
                [
                    'choices' => [
                        'chronological' => 'ASC',
                        'reverse' => 'DESC'
                    ],
                ]
            )
            ->add('global', CheckboxType::class, array(
                'required' => false
            ))
            ->add('save', SubmitType::class)
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Webelop\AlbumBundle\Entity\Tag'
        ));
    }

    public function getName()
    {
        return 'jcc_bundle_albumbundle_tagtype';
    }
}
