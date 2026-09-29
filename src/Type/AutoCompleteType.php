<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoCompleteType extends AbstractType
{
    use HtmlAttributesTrait;
    
    /**
     * 
     * @return string
     */
    public function getParent()
    {
        return TextType::class;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {   
        parent::configureOptions($resolver);
        $resolver->setRequired(['url', "dom_parent"]);
        // The previous normalizer returned a fixed array, discarding every
        // attribute supplied by the caller (id, action, ...). Merging keeps
        // caller attributes while still applying the autocomplete defaults.
        $resolver->addNormalizer('attr', function (Options $options, $value) {
            return array_merge($value, [
                'class' => $value['class'] ?? 'autocomplete form-control',
                'url' => $options['url'],
            ]);
        });
        
        
        $resolver->setDefaults([
            "dom_parent" => null
        ]);
    }
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        
        $view->vars["html_attributes"] = $this->buildHtmlAttributes($view->vars["attr"]);
        
        $view->vars["appendTo"] = $options["dom_parent"];
    }
}
