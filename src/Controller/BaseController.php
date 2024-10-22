<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Environment;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseController extends AbstractController implements TwigControllerInterface, TranslatorControllerInterface, SessionControllerInterface, DoctrineManagerRegistryControllerInterface
{
    private SessionInterface $session;
    protected TranslatorInterface $translator;
    private Environment $twig;
    private ManagerRegistry $managerRegistry;

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function setRequestStack(RequestStack $requestStack):void
    {
        $this->session = $requestStack->getSession();
    }

    public function getSession():SessionInterface
    {
        return $this->session;
    }

    /**
     *
     * @param string $view
     * @param array $parameters
     * @return string
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->getTwig()->render($view, $parameters);
    }

    /**
     * Streams a view.
     */
    protected function stream(string $view, array $parameters = [], StreamedResponse $response = null): StreamedResponse
    {
        if (!$this->twig) {
            throw new \LogicException('You cannot use the "stream" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        $twig = $this->getTwig();

        $callback = function () use ($twig, $view, $parameters) {
            $twig->display($view, $parameters);
        };

        if (null === $response) {
            return new StreamedResponse($callback);
        }

        $response->setCallback($callback);

        return $response;
    }

    protected function buildSuccessResult(string $type, $object)
    {
        return [
            "process" => true,
            "status" => true,
            "message" => $this->getTranslator()->trans('messages.' . $type . '.success'),
            "errors" => null
        ];
    }

    protected function buildErrorResult(string $type, string $message)
    {
        return [
            "process" => true,
            "status" => false,
            "message" => $this->getTranslator()->trans('messages.' . $type . '.error'),
            "errors" => $message
        ];
    }

    protected function processFormAjax(Request $request, FormInterface $form): array
    {
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {

            return ["process" => false];
        }

        $type = 'add';
        if (!$form->isValid()) {

            $errors = $this->getErrorsFromForm($form);

            return $this->buildErrorResult($type, implode(", ", $errors));
        }

        $manager = $this->getDoctrine()->getManager();
        try {

            $object = $this->saveObject($form->getData(), $manager);

            return $this->buildSuccessResult($type, $object);
        } catch (\Exception $ex) {

            return $this->buildErrorResult($type, $ex->getMessage());
        }
    }

    protected function saveObject($object, EntityManagerInterface $manager)
    {
        $manager->wrapInTransaction(function (EntityManagerInterface $em) use ($object) {
            $em->persist($object);
        });

        return $object;
    }

    protected function processForm(Request $request, FormInterface $form, $func = null)
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {

            return false;
        }

        if (!$form->isValid()) {
            $this->addFlash("error", implode(', ', $this->getErrorsFromForm($form)));

            return false;
        }

        $manager = $this->getDoctrine()->getManager();
        try {
            $object = $this->saveObject($form->getData(), $manager);

            $this->addFlash("info", $this->getSuccessMessage($object));

            return $object;
        } catch (\Exception $ex) {
            $this->addFlash("error", $this->getErrorMessage($ex));
        }

        return false;
    }

    protected function getSuccessMessage($object): string
    {
        return $this->getTranslator()->trans('successfull_save');
    }

    protected function getErrorMessage(\Exception $ex): string
    {
        return $ex->getMessage();
    }

    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if (!$childForm instanceof FormInterface) {
                continue;
            }

            $childErrors = $this->getErrorsFromForm($childForm);
            if (!$childErrors) {
                continue;
            }

            $errors[$childForm->getName()] = sprintf('%s: %s', $this->getTranslator()->trans($childForm->getName()), implode(", ", $childErrors));
        }

        return $errors;
    }

    protected function doDelete(Request $request, $object, string $tokenName): void
    {
        if (!$this->isCsrfTokenValid($tokenName, $request->request->get('_token'))) {
            $this->addFlash('error', $this->getTranslator()->trans('csrf_token_detected'));
            return;
        }

        $manager = $this->getDoctrine()->getManager();
        try {

            $this->removeObject($object, $manager);

            $this->addFlash('info', $this->getTranslator()->trans('successfull_delete'));
        } catch (\Exception $ex) {
            $this->addFlash('error', $this->getErrorMessage($ex));
        }
    }

    protected function removeObject($object, EntityManagerInterface $manager): void
    {
        $manager->wrapInTransaction(function (EntityManagerInterface $em) use ($object) {
            $em->remove($object);
        });

        if ($object->getId()) {
            $qb = $manager->createQueryBuilder('t')->delete(get_class($object), 'obj')->where('obj.id = :id')
                ->setParameter('id', $object->getId());
            $qb->getQuery()->execute();
        }
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry):void
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getDoctrine():ManagerRegistry
    {
        return $this->managerRegistry;
    }
}
