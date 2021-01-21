<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AutoCompleteType extends AbstractType
{
    
    public function getParent()
    {
        return TextType::class;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {   
        $resolver->setRequired('url');
        $resolver->addNormalizer('attr', function (Options $options) {
            return [
                'class' => 'form-control autocomplete', 'url' => $options['url']
            ];
        });
    }
    
}
