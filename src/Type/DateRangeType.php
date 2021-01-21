<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

class DateRangeType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $fromOpt = $options['from_options'];
        $fromOpt['required'] = false;
        $toOpt = $options['to_options'];
        $toOpt['required'] = false;
        $builder->add('from', DateType::class, $fromOpt)
                ->add('to', DateType::class, $toOpt);
    }
    
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'from_options' => [],
            'to_options' => []
        ]);
    }
    
}
