<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\HiddenTypeBundle\DataTransformer\ObjectToIdTransformer;
use Kematjaya\HiddenTypeBundle\DataTransformer\Transformer;

/**
 * Description of AutoCompleteEntityType
 *
 * @author programmer
 */
class AutoCompleteEntityType extends AbstractType
{
    private Transformer $transformer;

    public function __construct(private ManagerRegistry $registry)
    {
    }

    /**
     *
     * @return ?string
     */
    public function getParent():string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver):void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['url', "class", 'property_label']);

        $resolver->setDefaults([
            'property' => 'id'
        ]);

        $resolver->setAllowedTypes('property', ['null', 'string']);
        $resolver->setAllowedTypes('property_label', ['string']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $this->transformer = new ObjectToIdTransformer(
            $this->registry,
            $options['class'],
            $options['property']
        );

        $builder->addModelTransformer($this->transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options):void
    {
        parent::buildView($view, $form, $options);

        $view->vars["attr"]["class"] = $view->vars["attr"]["class"] ?? "autocomplete form-control";
        $view->vars["url"] = $options["url"];
        $attr = $view->vars["attr"];
        $view->vars["html_attributes"] = join(" ", array_map(function ($key) use ($attr) {
            return sprintf('%s="%s"', $key, $attr[$key]);
        }, array_keys($attr)));


        $view->vars["label_data"] = $this->getLabelData($view, $options);

    }

    protected function getLabelData(FormView $view, array $options): ?string
    {
        if (null == $view->vars["data"]) {

            return null;
        }

        $entity = $this->transformer->reverseTransform($view->vars["data"]);
        if (null === $entity) {
            return null;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        if (!$accessor->isReadable($entity, $options['property_label'])) {
            return null;
        }

        return $accessor->getValue($entity, $options['property_label']);
    }
}
