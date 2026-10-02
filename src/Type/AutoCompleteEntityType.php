<?php

namespace Kematjaya\BaseControllerBundle\Type;

use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\HiddenTypeBundle\DataTransformer\ObjectToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Description of AutoCompleteEntityType
 *
 * @author programmer
 */
class AutoCompleteEntityType extends AbstractType
{
    use HtmlAttributesTrait;

    /**
     * Form attribute under which each form keeps its own transformer.
     */
    private const string TRANSFORMER_ATTRIBUTE = 'kematjaya_object_to_id_transformer';

    public function __construct(private readonly ManagerRegistry $registry) {}

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['url', "class", 'property_label']);

        $resolver->setDefaults([
            'property'   => 'id',
        ]);

        $resolver->setAllowedTypes('property', ['null', 'string']);
        $resolver->setAllowedTypes('property_label', ['string']);
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new ObjectToIdTransformer(
            $this->registry,
            $options['class'],
            $options['property']
        );

        $builder->addModelTransformer($transformer);
        // The transformer is kept on the builder instead of on the type
        // instance. This type is a shared service, so storing it on $this made
        // buildView() label every field with whichever form was built last.
        $builder->setAttribute(self::TRANSFORMER_ATTRIBUTE, $transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars["attr"]["class"] ??= "autocomplete form-control";
        $view->vars["url"] = $options["url"];
        $view->vars["html_attributes"] = $this->buildHtmlAttributes($view->vars["attr"]);

        $transformer = $form->getConfig()->getAttribute(self::TRANSFORMER_ATTRIBUTE);
        $view->vars["label_data"] = $this->getLabelData($view, $options, $transformer);

    }

    protected function getLabelData(FormView $view, array $options, ?ObjectToIdTransformer $transformer = null): ?string
    {
        if (null == $view->vars["data"]) {

            return null;
        }

        if (null === $transformer) {
            return null;
        }

        $entity = $transformer->reverseTransform($view->vars["data"]);
        if (null === $entity) {
            return null;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        if (! $accessor->isReadable($entity, $options['property_label'])) {
            return null;
        }

        return $accessor->getValue($entity, $options['property_label']);
    }
}
