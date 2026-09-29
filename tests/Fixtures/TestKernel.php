<?php

namespace Kematjaya\BaseControllerBundle\Tests\Fixtures;

use Kematjaya\BaseControllerBundle\BaseControllerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;

/**
 * A real Symfony 6.4 kernel: FrameworkBundle for the HTTP/form/session stack,
 * TwigBundle and DoctrineBundle so the collaborators BaseController declares
 * actually exist and can be autowired through the bundle's compiler pass.
 *
 * Cache and log directories live in the system temp directory so a test run
 * never writes into the repository.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new BaseControllerBundle(),
        ];
    }

    /**
     * Stable path for the test database, derived from the project directory so
     * the ORM 2 and ORM 3 matrices never share a file.
     */
    public static function databaseFile(): string
    {
        return sys_get_temp_dir().'/bcb-kernel-'.md5(__DIR__).'/functional.sqlite';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        // Deliberately stable: the compiled container is expensive, so every
        // test in a run reuses the same cache instead of recompiling. The
        // directory is keyed by project path, so the ORM 2 and ORM 3 matrices
        // never share a container.
        return sys_get_temp_dir().'/bcb-kernel-'.md5(__DIR__).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/bcb-kernel-'.md5(__DIR__).'/log';
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'bcb-test-secret',
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
                'handler_id' => null,
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax',
            ],
            'router' => ['utf8' => true],
            'form' => true,
            'translator' => ['enabled' => true],
            'csrf_protection' => ['enabled' => true],
            'validation' => false,
            'php_errors' => ['log' => true],
        ]);

        $container->loadFromExtension('twig', [
            'default_path' => __DIR__.'/views',
            'strict_variables' => true,
        ]);

        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                // A file, not ":memory:". KernelBrowser reboots the kernel
                // between requests, which opens a fresh connection; an in-memory
                // database would come back empty and the schema would vanish.
                'path' => self::databaseFile(),
                'charset' => 'UTF8',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'mappings' => [
                    'BcBTest' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'Kematjaya\BaseControllerBundle\Tests\Fixtures\Entity',
                        'alias' => 'BcBTest',
                    ],
                ],
            ],
        ]);

        $container->loadFromExtension('security', [
            'password_hashers' => [
                'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => 'plaintext',
            ],
            'providers' => [
                'bcb_users' => [
                    'memory' => [
                        'users' => [
                            'test' => ['password' => 'test', 'roles' => ['ROLE_USER']],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => ['lazy' => true, 'provider' => 'bcb_users'],
            ],
        ]);

        // setAutoconfigured(true) puts the controller through the bundle's
        // registerForAutoconfiguration() tags, so the compiler pass has to
        // inject the collaborators for this to work at all.
        $container->register(ProbeController::class, ProbeController::class)
            ->addTag('controller.service_arguments')
            ->setAutoconfigured(true)
            ->setAutowired(true)
            ->setPublic(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('probe_session', '/probe/session')
            ->controller([ProbeController::class, 'session']);

        $routes->add('probe_save', '/probe/save')
            ->controller([ProbeController::class, 'save']);

        $routes->add('probe_count', '/probe/count')
            ->controller([ProbeController::class, 'count']);

        $routes->add('probe_rollback', '/probe/rollback')
            ->controller([ProbeController::class, 'rollback']);

        $routes->add('probe_delete', '/probe/delete/{id}')
            ->controller([ProbeController::class, 'delete']);

        $routes->add('probe_csrf', '/probe/csrf/{id}')
            ->controller([ProbeController::class, 'csrf']);

        $routes->add('probe_render', '/probe/render')
            ->controller([ProbeController::class, 'renderPage']);

        $routes->add('probe_form', '/probe/form')
            ->controller([ProbeController::class, 'form']);
    }
}
