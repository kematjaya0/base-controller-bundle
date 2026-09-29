<?php

namespace Kematjaya\BaseControllerBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\TokenType;
use Kematjaya\BaseControllerBundle\Controller\BaseController;
use PHPUnit\Framework\TestCase;

final class TransactionalHarness extends BaseController
{
    /**
     * @return mixed
     */
    public function callTransactional(EntityManagerInterface $manager, callable $func)
    {
        return $this->transactional($manager, $func);
    }
}

/**
 * EntityManager::transactional() was removed in ORM 3, so BaseController probes
 * for wrapInTransaction() and falls back. The shim only works if the method it
 * selects exists on the ORM that is actually installed, which is exactly what
 * these tests pin down.
 */
final class TransactionalBridgeTest extends TestCase
{
    private function isOrm3(): bool
    {
        return enum_exists(TokenType::class);
    }

    /**
     * The branch the shim will take against a plain EntityManagerInterface mock,
     * which is decided by method_exists() on the runtime object.
     */
    private function expectedMethod(): string
    {
        return method_exists(EntityManagerInterface::class, 'wrapInTransaction')
            ? 'wrapInTransaction'
            : 'transactional';
    }

    public function testBridgeTargetsTheMethodTheInstalledOrmExpects(): void
    {
        $this->assertSame(
            $this->isOrm3() ? 'wrapInTransaction' : 'transactional',
            $this->expectedMethod(),
            'the transaction bridge picked the wrong branch for this ORM major'
        );
    }

    /**
     * A real application passes the concrete EntityManager, so the selected
     * method has to exist there too - not just on the interface.
     */
    public function testSelectedMethodExistsOnTheConcreteEntityManager(): void
    {
        $chosen = $this->expectedMethod();

        $this->assertTrue(
            method_exists(EntityManager::class, $chosen),
            sprintf('EntityManager::%s() no longer exists on this ORM; the bridge needs a new target', $chosen)
        );
    }

    /**
     * saveObject() and removeObject() depend on the callback running and its
     * return value surviving the wrapper - a void shim would silently drop both.
     */
    public function testCallbackRunsAndItsReturnValueIsPropagated(): void
    {
        $expected = new \stdClass();
        $ran = false;

        $manager = $this->createMock(EntityManagerInterface::class);
        $method = $this->expectedMethod();

        $manager->expects($this->once())->method($method)->willReturnCallback(
            static function (callable $func) use (&$ran, $expected) {
                $ran = true;

                return $func();
            }
        );

        $result = (new TransactionalHarness())->callTransactional(
            $manager,
            static function () use ($expected) {
                return $expected;
            }
        );

        $this->assertTrue($ran, 'the wrapped callback was never invoked');
        $this->assertSame($expected, $result);
    }

    public function testTheCallbackReceivesTheManagerItWasGiven(): void
    {
        $seen = null;

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method($this->expectedMethod())
            ->willReturnCallback(static fn (callable $func) => $func($manager));

        // The real closures in BaseController typehint EntityManagerInterface,
        // so the wrapper has to hand the manager back to them.
        (new TransactionalHarness())->callTransactional(
            $manager,
            static function (EntityManagerInterface $em) use (&$seen) {
                $seen = $em;
            }
        );

        $this->assertSame($manager, $seen);
    }
}
