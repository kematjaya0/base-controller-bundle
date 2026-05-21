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
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['url', 'dom_parent']);
        $resolver->addNormalizer('attr', static function (Options $options) {
            return [
                'class' => 'autocomplete form-control', 'url' => $options['url'],
            ];
        });

        $resolver->setDefaults([
            'dom_parent' => null,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $attr = $view->vars['attr'];
        $view->vars['html_attributes'] = implode(' ', array_map(static function ($key) use ($attr) {
            return \sprintf('%s="%s"', $key, $attr[$key]);
        }, array_keys($attr)));

        $view->vars['appendTo'] = $options['dom_parent'];
    }
}
