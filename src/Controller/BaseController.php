<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseController extends AbstractController implements TwigControllerInterface, TranslatorControllerInterface, SessionControllerInterface, DoctrineManagerRegistryControllerInterface
{
    protected TranslatorInterface $translator;

    private Environment $twig;

    /**
     * Injected via the compiler pass: Symfony 5.0 removed getDoctrine() from
     * AbstractController, so it is restored here for backwards compatibility.
     */
    private ManagerRegistry $managerRegistry;

    private ?RequestStack $requestStack = null;

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

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getDoctrine(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Resolves the session of the current request.
     *
     * Replaces the container lookup `$this->get('session')`, which relied on
     * ControllerTrait::get() — removed in Symfony 5.0. RequestStack is used
     * instead of the container `session` service, which no longer exists in
     * recent FrameworkBundle releases and is not autowirable in any of them.
     */
    public function getSession(): SessionInterface
    {
        if (null === $this->requestStack) {
            throw new \LogicException('The request stack is not available. Make sure the controller is registered as a service and tagged by the BaseControllerBundle compiler pass.');
        }

        return $this->requestStack->getSession();
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->getTwig()->render($view, $parameters);
    }

    /**
     * Streams a view.
     */
    protected function stream(string $view, array $parameters = [], ?StreamedResponse $response = null): StreamedResponse
    {
        if (!$this->twig) {
            throw new \LogicException('You cannot use the "stream" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        $twig = $this->getTwig();

        $callback = function () use ($twig, $view, $parameters): void {
            $twig->display($view, $parameters);
        };

        if (null === $response) {
            return new StreamedResponse($callback);
        }

        $response->setCallback($callback);

        return $response;
    }

    protected function buildSuccessResult(string $type, mixed $object): array
    {
        return [
            "process" => true,
            "status" => true,
            "message" => $this->getTranslator()->trans('messages.' . $type . '.success'),
            "errors" => null,
        ];
    }

    protected function buildErrorResult(string $type, string $message): array
    {
        return [
            "process" => true,
            "status" => false,
            "message" => $this->getTranslator()->trans('messages.' . $type . '.error'),
            "errors" => $message,
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

    protected function saveObject(object $object, EntityManagerInterface $manager): object
    {
        $this->transactional($manager, function (EntityManagerInterface $em) use ($object): void {
            $em->persist($object);
        });

        return $object;
    }

    /**
     * Runs $func inside a transaction on both ORM 2 and ORM 3.
     *
     * ORM 3 removed EntityManager::transactional() entirely, while
     * wrapInTransaction() exists in ORM 2.19+ and 3.x, so it is the only call
     * available on every supported version and the transactional() branch is
     * only reached by EntityManagerInterface implementations that predate it.
     *
     * Note that on failure wrapInTransaction() closes the EntityManager: both
     * ORM 2.20 and ORM 3.7 roll back and then call close() from a finally block.
     * The transaction is rolled back correctly, but the manager must not be
     * reused afterwards. Callers that need to keep working after a failed save
     * should obtain a fresh manager from the registry.
     */
    protected function transactional(EntityManagerInterface $manager, callable $func): mixed
    {
        if (method_exists($manager, 'wrapInTransaction')) {
            return $manager->wrapInTransaction($func);
        }

        return $manager->transactional($func);
    }

    protected function processForm(Request $request, FormInterface $form, ?callable $func = null): object|false
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

    protected function getSuccessMessage(mixed $object): string
    {
        return $this->getTranslator()->trans('successfull_save');
    }

    protected function getErrorMessage(\Exception $ex): string
    {
        return $ex->getMessage();
    }

    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];
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

    protected function doDelete(Request $request, object $object, string $tokenName): void
    {
        // The token is read from the body first, then from the query string.
        // Symfony's Request::create() puts parameters for DELETE in the body,
        // so tests and AJAX calls keep working; a plain browser link cannot
        // send a DELETE body at all, so those have to use the query string.
        $token = $request->request->get('_token') ?? $request->query->get('_token');

        if (!$this->isCsrfTokenValid($tokenName, $token)) {
            $this->addFlash('error', $this->getTranslator()->trans('csrf_token_detected'));
            return;
        }

        $manager = $this->getDoctrine()->getManager();
        try {

            $this->removeObject($object, $manager);

            $this->addFlash("info", $this->getTranslator()->trans('successfull_delete'));
        } catch (\Exception $ex) {
            $this->addFlash("error", $this->getErrorMessage($ex));
        }
    }

    protected function removeObject(object $object, EntityManagerInterface $manager): void
    {
        // The previous implementation also issued a raw DQL DELETE for the same
        // row. That bypassed the identity map, cascade rules and lifecycle
        // events, and could remove a row without cascading to its children.
        $this->transactional($manager, function (EntityManagerInterface $em) use ($object): void {
            $em->remove($object);
        });
    }
}
