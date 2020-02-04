<?php

namespace Webelop\AlbumBundle\Tests\Fixtures\App;

/**
 * Class AppKernel
 */
class AppKernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

    public function __construct(string $env = 'test', bool $debug = false)
    {
        parent::__construct($env, $debug);
    }

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new \Webelop\AlbumBundle\WebelopAlbumBundle(),
        ];
    }

    protected function configureRoutes(\Symfony\Component\Routing\RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__ . '/../../../src/Resources/config/routes.xml', '/');
        $routes->import(__DIR__ . '/../../../tests/Fixtures/App/config/routes.xml', '/');
    }

    protected function configureContainer(
        \Symfony\Component\DependencyInjection\ContainerBuilder $c,
        \Symfony\Component\Config\Loader\LoaderInterface $loader
    )
    {
        $this->configureFixtures($c);

        $c->loadFromExtension('webelop_album', [
            'album_root' => __DIR__ . '/Pictures',
            'cache_path' => __DIR__ . '/../../../build/pictures',
            'salt' => 'PassTheSalt!',
            'use_binary_file_response' => false, // BinaryFileResponse is not compatible with Functional testing
        ]);

        $c->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'S3CR3T1'
        ]);

        // config/packages/test/security.php
        $c->loadFromExtension('security', [
            'encoders' => [
                'Webelop\AlbumBundle\Tests\Fixtures\App\Entity\User' => 'plaintext',
            ],
            'providers' => [
                'app_user_provider' => [
                    'entity' => [
                        'class' => 'Webelop\AlbumBundle\Tests\Fixtures\App\Entity\User',
                        'property' => 'email',
                    ]
                ]
            ],

            # https://symfony.com/doc/current/security.html#where-do-users-come-from-user-providers
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'anonymous' => true,
                    'logout' => [
                        'path' => 'app_logout',
                        'target' => '/',
                        'invalidate_session' => true,
                    ],
                    'http_basic' => [
                        'provider' => 'app_user_provider',
                    ],
                ],
            ],

            'access_control' => [
                ['path' => '^/manager', 'roles' => 'ROLE_ADMIN'],
            ],

        ]);

        $c->loadFromExtension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'debug' => '%kernel.debug%',
            'strict_variables' => '%kernel.debug%',
            'form_themes' => ['bootstrap_4_layout.html.twig'],
        ]);

        $c->loadFromExtension('doctrine', [
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Webelop\AlbumBundle\Tests\Fixtures\App\Entity' => [
                        'type' => 'annotation',
                        'dir' => __DIR__ . '/Entity',
                        'is_bundle' => false,
                        'prefix' => 'Webelop\AlbumBundle\Tests\Fixtures\App\Entity',
                        'alias' => 'FixturesApp',
                    ],
                ],
            ],
            'dbal' => [
                'connections' => [
                    'default' => [
                        'driver' => 'pdo_sqlite',
                        'path' => __DIR__ . '/../../../build/test.db'
                    ]
                ]
            ]
        ]);
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../../../build/cache/' . $this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../../../build/kernel_logs/' . $this->getEnvironment();
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $c
     */
    private function configureFixtures(\Symfony\Component\DependencyInjection\ContainerBuilder $c): void
    {
        $fixtures = new \Symfony\Component\DependencyInjection\Definition(
            \Webelop\AlbumBundle\Tests\Fixtures\DataFixtures::class
        );
        $fixtures->addTag('doctrine.fixture.orm');
        $c->setDefinition('webelop_album.test.fixtures', $fixtures);
    }
}