<?php

namespace Kematjaya\BaseControllerBundle\Tests\CompilerPass;

use Kematjaya\BaseControllerBundle\BaseControllerBundle;
use Kematjaya\BaseControllerBundle\CompilerPass\ControllerCompilerPass;
use Kematjaya\BaseControllerBundle\Controller\DoctrineManagerRegistryControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\LexikFilterControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\PaginationControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\SessionControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\TranslatorControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\TwigControllerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * The bundle tags six controller interfaces in build() and ControllerCompilerPass
 * looks the tags up again to decide which setter to call. Those two sides are
 * written independently and only meet at runtime, so this test drives the real
 * pair: read the tag the bundle actually registers, tag a definition with it,
 * then assert the pass injects the matching setter.
 *
 * Deliberately no container compile and no autowiring, so the test needs no
 * Twig, no database and no kernel.
 */
final class ControllerCompilerPassTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public function interfaceProvider(): array
    {
        return [
            'twig' => [TwigControllerInterface::class, 'setTwig'],
            'translator' => [TranslatorControllerInterface::class, 'setTranslator'],
            'paginator' => [PaginationControllerInterface::class, 'setPaginator'],
            'filter' => [LexikFilterControllerInterface::class, 'setFilterBuilderUpdater'],
            'session' => [SessionControllerInterface::class, 'setRequestStack'],
            'manager registry' => [DoctrineManagerRegistryControllerInterface::class, 'setManagerRegistry'],
        ];
    }

    /**
     * Reads the tags BaseControllerBundle::build() registered per interface.
     *
     * @return array<class-string, list<string>>
     */
    private function autoconfiguredTags(ContainerBuilder $container): array
    {
        $property = new \ReflectionProperty(ContainerBuilder::class, 'autoconfiguredInstanceof');
        $property->setAccessible(true);

        $map = [];
        foreach ($property->getValue($container) as $interface => $childDefinition) {
            // registerForAutoconfiguration() stores a single ChildDefinition per
            // interface, not a collection of them.
            $map[$interface] = array_keys($childDefinition->getTags());
        }

        return $map;
    }

    /**
     * The tag name and the setter must stay in step on both sides.
     *
     * @dataProvider interfaceProvider
     */
    public function testBundleTagForAnInterfaceDrivesTheMatchingSetter(string $interface, string $setter): void
    {
        $container = new ContainerBuilder();
        (new BaseControllerBundle())->build($container);

        $tags = $this->autoconfiguredTags($container);
        $this->assertArrayHasKey(
            $interface,
            $tags,
            $interface.' is not registered for autoconfiguration'
        );
        $this->assertCount(1, $tags[$interface], $interface.' must declare exactly one tag');

        $definition = new Definition(\stdClass::class);
        $definition->addTag($tags[$interface][0]);
        $container->setDefinition('test.controller', $definition);

        (new ControllerCompilerPass())->process($container);

        $calls = $container->getDefinition('test.controller')->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertSame($setter, $calls[0][0]);
        $this->assertSame([], $calls[0][1], 'the setter is called with no explicit arguments');
    }

    public function testUntaggedServicesAreLeftAlone(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('plain.service', new Definition(\stdClass::class));

        (new ControllerCompilerPass())->process($container);

        $this->assertSame([], $container->getDefinition('plain.service')->getMethodCalls());
    }

    public function testAnEmptyContainerProcessesWithoutError(): void
    {
        $container = new ContainerBuilder();

        (new ControllerCompilerPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testThePassIsRegisteredByTheBundle(): void
    {
        $container = new ContainerBuilder();
        (new BaseControllerBundle())->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $classes = array_map(static fn (object $pass) => get_class($pass), $passes);

        $this->assertContains(ControllerCompilerPass::class, $classes);
    }

    /**
     * Every interface the bundle advertises must be covered, so a new interface
     * cannot be added without a corresponding setter injection.
     */
    public function testNoInterfaceIsRegisteredForAutoconfigurationWithoutASetter(): void
    {
        $container = new ContainerBuilder();
        (new BaseControllerBundle())->build($container);

        // The provider is keyed by dataset name, so take the FQCN from column 0.
        $known = array_column($this->interfaceProvider(), 0);

        foreach (array_keys($this->autoconfiguredTags($container)) as $interface) {
            $this->assertContains($interface, $known, $interface.' has no test coverage');
        }
        $this->assertCount(count($known), $this->autoconfiguredTags($container));
    }
}
