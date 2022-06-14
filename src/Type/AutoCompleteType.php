<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
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
        parent::configureOptions($resolver);
        $resolver->setRequired(['url', "dom_parent"]);
        $resolver->addNormalizer('attr', function (Options $options) {
            return [
                'class' => 'autocomplete form-control', 'url' => $options['url']
            ];
        });
        
        
        $resolver->setDefaults([
            "dom_parent" => null
        ]);
    }
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        
        $attr = $view->vars["attr"];
        $view->vars["html_attributes"] = join(" ", array_map(function ($key) use ($attr) {
            return sprintf('%s="%s"', $key, $attr[$key]);
        }, array_keys($attr)));
        
        $view->vars["appendTo"] = $options["dom_parent"];
    }
}
