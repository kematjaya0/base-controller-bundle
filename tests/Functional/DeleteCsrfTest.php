<?php

namespace Kematjaya\BaseControllerBundle\Tests\Functional;

use Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity\SampleEntity;

/**
 * doDelete() must never remove a row unless the CSRF token is valid, and it
 * must accept the token from the request body as well as from the query string
 * (a browser link cannot send a DELETE body). Covered in README 3.1.
 */
final class DeleteCsrfTest extends FunctionalTestCase
{
    public function testAValidTokenInTheRequestBodyDeletesTheRow(): void
    {
        $id = $this->seedEntity('body');
        $token = $this->csrfToken('delete' . $id);

        $this->client->request('DELETE', '/probe/delete/' . $id, ['_token' => $token]);

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->requestPayload()['remaining']);
    }

    public function testAValidTokenInTheQueryStringDeletesTheRow(): void
    {
        $id = $this->seedEntity('query');
        $token = $this->csrfToken('delete' . $id);

        $this->client->request('DELETE', '/probe/delete/' . $id . '?_token=' . urlencode($token));

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->requestPayload()['remaining']);
    }

    public function testAnInvalidTokenLeavesTheRowInPlace(): void
    {
        $id = $this->seedEntity('kept-invalid');

        $this->client->request('DELETE', '/probe/delete/' . $id, ['_token' => 'not-the-right-token']);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->requestPayload()['remaining'], 'an invalid token must not delete');
    }

    public function testAMissingTokenLeavesTheRowInPlace(): void
    {
        $id = $this->seedEntity('kept-missing');

        $this->client->request('DELETE', '/probe/delete/' . $id);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->requestPayload()['remaining'], 'a missing token must not delete');
    }

    /**
     * A token minted for a different id must not authorise this delete, so the
     * token name has to be bound to the entity.
     */
    public function testATokenForAnotherIdLeavesTheRowInPlace(): void
    {
        $id = $this->seedEntity('kept-wrong-id');
        $otherToken = $this->csrfToken('delete' . ($id + 1000));

        $this->client->request('DELETE', '/probe/delete/' . $id, ['_token' => $otherToken]);

        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->requestPayload()['remaining'], 'a token for another id must not delete');
    }

    public function testTheEntityIsStillManagedAfterARejectedDelete(): void
    {
        $id = $this->seedEntity('still-managed');

        $this->client->request('DELETE', '/probe/delete/' . $id, ['_token' => 'bad']);

        $this->entityManager()->clear();

        self::assertNotNull(
            $this->entityManager()->getRepository(SampleEntity::class)->find($id)
        );
    }
}
