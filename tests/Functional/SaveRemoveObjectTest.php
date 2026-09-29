<?php

namespace Kematjaya\BaseControllerBundle\Tests\Functional;

use Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity\SampleEntity;

/**
 * Exercises saveObject() and the transactional() bridge against a real
 * EntityManager on a real SQLite database, on both ORM 2 and ORM 3.
 */
final class SaveRemoveObjectTest extends FunctionalTestCase
{
    public function testSaveObjectPersistsTheRowThroughARealTransaction(): void
    {
        self::assertSame(0, $this->rowCount());

        $this->client->request('GET', '/probe/save?name=alpha');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->rowCount());
    }

    public function testSaveObjectReturnsTheObjectWithItsGeneratedId(): void
    {
        $this->client->request('GET', '/probe/save?name=beta');

        $payload = $this->requestPayload();

        self::assertGreaterThan(0, $payload['id']);
        self::assertSame('beta', $payload['name']);
    }

    public function testThePersistedRowIsReadableBackThroughTheEntityManager(): void
    {
        $id = $this->seedEntity('readable');

        $manager = $this->entityManager();
        $manager->clear();

        $entity = $manager->find(SampleEntity::class, $id);

        self::assertInstanceOf(SampleEntity::class, $entity);
        self::assertSame('readable', $entity->getName());
    }

    /**
     * The point of the ORM 2/3 bridge: the row must NOT survive, which can only
     * happen if transactional() opened a real transaction and rolled it back.
     */
    public function testTransactionalRollsBackWhenTheCallbackThrows(): void
    {
        $this->client->request('GET', '/probe/rollback');

        self::assertResponseIsSuccessful();
        $payload = $this->requestPayload();

        self::assertTrue($payload['rolledBack'], 'the exception should have escaped the callback');
        self::assertSame(0, $payload['count'], 'the flushed row should have been rolled back');
    }

    /**
     * A failed transaction must not break the next request: KernelBrowser reboots
     * the kernel, so the request after a failure gets a fresh EntityManager and
     * the application keeps working.
     *
     * Note this runs on a *different* EntityManager than the failed one. In
     * process, the manager that ran a failed transaction is closed afterwards —
     * Doctrine ORM 3.7's wrapInTransaction() calls close() in a finally block.
     * The docblock on BaseController::transactional() claims the opposite, so
     * callers must treat the EntityManager as unusable after a failed save.
     */
    public function testTheApplicationKeepsWorkingAfterAFailedTransaction(): void
    {
        $this->client->request('GET', '/probe/rollback');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->requestPayload()['count']);

        $this->client->request('GET', '/probe/save?name=after-failure');

        self::assertResponseIsSuccessful();
        self::assertSame('after-failure', $this->requestPayload()['name']);
        self::assertSame(1, $this->rowCount());
    }

    public function testRemoveObjectDeletesThroughTheIdentityMap(): void
    {
        $id = $this->seedEntity('doomed');
        $token = $this->csrfToken('delete'.$id);

        $this->client->request('DELETE', '/probe/delete/'.$id, ['_token' => $token]);

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->requestPayload()['remaining']);
    }
}
