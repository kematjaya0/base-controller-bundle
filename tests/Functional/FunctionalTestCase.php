<?php

namespace Kematjaya\BaseControllerBundle\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity\SampleEntity;
use Kematjaya\BaseControllerBundle\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Boots the real TestKernel and builds a real schema in a file-backed SQLite
 * database. Nothing here is mocked: the container, EntityManager, session,
 * router and Twig all come from the real Symfony stack.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * Created once per test by setUp(); WebTestCase refuses to boot the kernel
     * twice, so tests must reuse this instead of calling createClient().
     */
    protected KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Start every test from an empty database. The file is shared by all
        // tests in a run, so it has to be removed rather than dropped; the
        // kernel is shut down at this point, so no connection holds it open.
        $this->resetDatabaseFile();

        $this->client = static::createClient();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::ensureKernelShutdown();
    }

    private function resetDatabaseFile(): void
    {
        $file = TestKernel::databaseFile();

        if (is_file($file)) {
            unlink($file);
        }
    }

    private function createSchema(): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        if ([] === $metadata) {
            throw new \LogicException('no ORM metadata was found; the TestKernel mapping is wrong');
        }

        // The file was just removed, so there is nothing to drop.
        (new SchemaTool($manager))->createSchema($metadata);
    }

    /**
     * Returns a real CSRF token minted by the real token manager, so doDelete()
     * is exercised against the same validation path an application would use.
     */
    protected function csrfToken(string $id): string
    {
        $this->client->request('GET', '/probe/csrf/'.$id);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);

        return $payload['token'];
    }

    protected function requestPayload(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    protected function rowCount(): int
    {
        $this->client->request('GET', '/probe/count');

        return (int) $this->requestPayload()['count'];
    }

    protected function entityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Inserts a row through the real EntityManager and returns its id.
     */
    protected function seedEntity(string $name = 'seed'): int
    {
        $entity = new SampleEntity();
        $entity->setName($name);

        $this->entityManager()->persist($entity);
        $this->entityManager()->flush();

        return (int) $entity->getId();
    }
}
