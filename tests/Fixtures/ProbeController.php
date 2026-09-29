<?php

namespace Kematjaya\BaseControllerBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Kematjaya\BaseControllerBundle\Controller\BaseController;
use Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity\SampleEntity;
use Kematjaya\BaseControllerBundle\Type\AutoCompleteType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exposes the inherited BaseController behaviour over HTTP so the functional
 * tests exercise the real container, a real EntityManager and a real session
 * instead of mocks.
 */
final class ProbeController extends BaseController
{
    public function session(): Response
    {
        $session = $this->getSession();
        $session->set('probe', 'value');

        return new JsonResponse([
            'sessionClass' => get_class($session),
            'probe' => $session->get('probe'),
        ]);
    }

    public function save(Request $request): Response
    {
        $manager = $this->getDoctrine()->getManager();

        $entity = new SampleEntity();
        $entity->setName((string) $request->query->get('name', 'probe'));

        $this->saveObject($entity, $manager);
        $manager->flush();

        return new JsonResponse([
            'id' => (int) $entity->getId(),
            'name' => $entity->getName(),
        ]);
    }

    /**
     * Persists and flushes inside transactional(), then throws. The row must not
     * survive: that is only true if a real transaction was opened and rolled
     * back, which is what distinguishes the ORM 2 / ORM 3 bridge from a bare
     * persist() call.
     */
    public function rollback(): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $entity = new SampleEntity();
        $entity->setName('ghost');

        $rolledBack = false;

        try {
            $this->transactional($manager, function (EntityManagerInterface $em) use ($entity): void {
                $em->persist($entity);
                $em->flush();

                throw new \RuntimeException('rollback please');
            });
        } catch (\RuntimeException $exception) {
            $rolledBack = true;
        }

        // wrapInTransaction() keeps the EntityManager usable, so this query
        // only succeeds if the preferred branch was taken on both ORM versions.
        $count = (int) $manager->getConnection()->fetchOne('SELECT COUNT(*) FROM sample_entity');

        return new JsonResponse([
            'rolledBack' => $rolledBack,
            'count' => $count,
        ]);
    }

    public function count(): Response
    {
        $count = (int) $this->getDoctrine()
            ->getManager()
            ->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM sample_entity');

        return new JsonResponse(['count' => $count]);
    }

    public function csrf(string $id): Response
    {
        $token = $this->container->get('security.csrf.token_manager')->getToken($id);

        return new JsonResponse(['token' => $token->getValue()]);
    }

    public function delete(Request $request, int $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $entity = $manager->find(SampleEntity::class, $id);

        if (null === $entity) {
            throw new NotFoundHttpException('no such entity');
        }

        $this->doDelete($request, $entity, 'delete'.$id);
        $manager->flush();

        $remaining = $manager->getConnection()->fetchOne('SELECT COUNT(*) FROM sample_entity');

        return new JsonResponse(['remaining' => (int) $remaining]);
    }

    public function renderPage(): Response
    {
        // renderView() needs the Twig collaborator the compiler pass injected.
        return new Response($this->renderView('probe.html.twig', ['label' => 'rendered']));
    }

    public function form(Request $request): Response
    {
        // The probe attribute is caller supplied so the test can prove the
        // generated attribute string is escaped, not interpolated raw.
        $attr = [
            'id' => 'cityField',
            'data-test' => 'kept',
        ];

        if (null !== $request->query->get('class')) {
            $attr['class'] = (string) $request->query->get('class');
        }

        if (null !== $request->query->get('probe')) {
            $attr['data-probe'] = (string) $request->query->get('probe');
        }

        $form = $this->createFormBuilder()
            ->add('city', AutoCompleteType::class, [
                'url' => '/autocomplete/city',
                'dom_parent' => '#city-list',
                'attr' => $attr,
            ])
            ->getForm();

        // createView(), not getViewData(): the latter returns the field data
        // (a string here), not the view variables under test.
        $view = $form->get('city')->createView();

        return new JsonResponse([
            'html_attributes' => $view->vars['html_attributes'],
            'appendTo' => $view->vars['appendTo'],
        ]);
    }
}
