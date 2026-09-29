<?php

namespace Kematjaya\BaseControllerBundle\Tests\Functional;

use Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity\SampleEntity;

/**
 * Proves the TestKernel can actually boot and serve a request, which is the
 * precondition for every other functional test in this directory.
 */
final class KernelSmokeTest extends FunctionalTestCase
{
    public function testTheKernelBootsAndServesARequest(): void
    {
        $this->client->request('GET', '/probe/render');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('rendered', (string) $this->client->getResponse()->getContent());
    }

    public function testTheCompilerPassInjectedTheCollaborators(): void
    {
        $this->client->request('GET', '/probe/session');

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);

        // A null session would mean setRequestStack() was never injected.
        self::assertNotNull($payload['sessionClass']);
        self::assertSame('value', $payload['probe']);
    }

    public function testTheEntityManagerIsRealAndMapped(): void
    {
        $manager = $this->entityManager();

        $class = $manager->getClassMetadata(SampleEntity::class)->getName();

        self::assertSame(SampleEntity::class, $class);
        self::assertTrue($manager->getConnection()->isConnected());
    }
}
