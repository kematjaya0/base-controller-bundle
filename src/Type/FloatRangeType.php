<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FloatRangeType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $fromOpt = $options['from_options'];
        $fromOpt['required'] = false;
        $toOpt = $options['to_options'];
        $toOpt['required'] = false;
        
        $builder->add('from', NumberType::class, $fromOpt)
                ->add('to', NumberType::class, $toOpt);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'from_options' => [],
            'to_options' => []
        ]);
    }
}
