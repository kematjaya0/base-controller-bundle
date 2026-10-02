<?php

namespace Kematjaya\BaseControllerBundle\Type;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Kematjaya\BaseControllerBundle\Type
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class PhoneNumberType extends AbstractType
{
    private readonly PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // The error is held in a per-form closure, not on the type instance.
        // This type is a shared service, so keeping it on $this leaked the
        // error of one invalid submit into every later form in the same request
        // (and across requests in a long-running worker).
        $error = null;

        $builder->addModelTransformer(new CallbackTransformer(function ($value) use ($options): ?string {
            if (null === $value) {
                return $value;
            }

            $prefix = $this->getPhonePrefix($options['region']);

            return trim(str_replace($prefix, "", $value));
        }, function ($value) use ($options, &$error) {
            // Always start from a clean slate: the same form instance can be
            // submitted more than once.
            $error = null;

            if (null === $value) {
                return null;
            }

            $value = trim(str_replace("-", "", str_replace($this->getPhonePrefix($options['region']), "", $value)));
            $value = $this->getPhonePrefix($options['region']) . preg_replace("/[a-z]/i", "", $value);
            try {
                $phoneNumber = $this->phoneUtil->parse(trim($value), $options['region']);
            } catch (\Exception $ex) {
                $error = $ex->getMessage();

                return null;
            }

            if (!$this->phoneUtil->isValidNumber($phoneNumber)) {
                $ex = $this->phoneUtil->getExampleNumber($options['region']);
                $error = sprintf("please insert a valid number, e.g: %s", $this->phoneUtil->format($ex, PhoneNumberFormat::INTERNATIONAL));

                return null;
            }

            return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        }));

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use (&$error): void {
            if (null !== $error) {
                $event->getForm()
                        ->addError(new FormError($error));
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['phone_prefix'] = $this->getPhonePrefix($options['region']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('region');
        $resolver->setDefaults([
            'region' => 'ID',
            'invalid_message' => fn(Options $options, $previousValue) => ($options['legacy_error_messages'] ?? true)
                ? $previousValue
                : 'Please enter a valid phone number.',
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'phone';
    }

    protected function getPhonePrefix(string $region): string
    {
        return sprintf("%s%s", PhoneNumberUtil::PLUS_SIGN, $this->phoneUtil->getCountryCodeForRegion($region));
    }
}
