<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FloatRangeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $fromOpt = $options['from_options'];
        $fromOpt['required'] = false;
        $toOpt = $options['to_options'];
        $toOpt['required'] = false;

        if (true === $options["html5"]) {
            $fromOpt['html5'] = true;
            $toOpt['html5'] = true;
            $builder->add('from', NumberType::class, $fromOpt)
                ->add('to', NumberType::class, $toOpt);
            return;
        }

        $builder->add('from', TextType::class, $fromOpt)
            ->add('to', TextType::class, $toOpt);
    }

    public function configureOptions(OptionsResolver $resolver):void
    {
        $resolver->setDefaults([
            'from_options' => [],
            'to_options' => [],
            "html5" => true
        ]);
    }
}
