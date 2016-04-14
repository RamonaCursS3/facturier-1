<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VatRateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, array(
                'translation_domain'=>'AppBundle',
            ))
            ->add('description', TextType::class, array(
                'translation_domain'=>'AppBundle',
            ))
            ->add('rate', TextType::class, array(
                'translation_domain'=>'AppBundle',
            ))
//            ->add('datCre', 'datetime')
//            ->add('datUpd', 'datetime')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\VatRate'
        ));
    }
}