<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use ApiPlatform\Core\Tests\Behat\DoctrineContext;
use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpClient\Messenger\PingWebhookMessageHandler;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Core\User\User as SymfonyCoreUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Config\Doctrine\Orm\EntityManagerConfig;
use Symfony\Config\Doctrine\OrmConfig;

/**
 * AppKernel for tests.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        // patch for behat/symfony2-extension not supporting %env(APP_ENV)%
        $this->environment = $_SERVER['APP_ENV'] ?? $environment;

        // patch for old versions of Doctrine Inflector, to delete when we'll drop support for v1
        // see https://github.com/doctrine/inflector/issues/147#issuecomment-628807276
        if (!class_exists(InflectorFactory::class)) { // @phpstan-ignore-next-line
            Inflector::rules('plural', ['/taxon/i' => 'taxa']);
        }
    }

    public function registerBundles(): array
    {
        $bundles = [
            new ApiPlatformBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new MercureBundle(),
            new SecurityBundle(),
            new WebProfilerBundle(),
            new FriendsOfBehatSymfonyExtensionBundle(),
            new FrameworkBundle(),
            new MakerBundle(),
        ];

        if (class_exists(DoctrineMongoDBBundle::class)) {
            $bundles[] = new DoctrineMongoDBBundle();
        }

        if (class_exists(NelmioApiDocBundle::class)) {
            $bundles[] = new NelmioApiDocBundle();
        }

        $bundles[] = new TestBundle();

        return $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes($routes)
    {
        $routes->import(__DIR__."/config/routing_{$this->getEnvironment()}.yml");

        if (class_exists(NelmioApiDocBundle::class)) {
            $routes->import('@NelmioApiDocBundle/Resources/config/routing.yml', '/nelmioapidoc');
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.project_dir', __DIR__);

        $loader->load(__DIR__."/config/config_{$this->getEnvironment()}.yml");

        if ('test' === $this->getEnvironment()) {
            $loader->load(__DIR__.'/config/config_doctrine.yml');
        }

        /* @TODO remove this check in 3.0 */
        if (\PHP_VERSION_ID >= 70200 && class_exists(Uuid::class) && class_exists(UuidType::class)) {
            $loader->load(__DIR__.'/config/config_symfony_uid.yml');
        }

        $c->getDefinition(DoctrineContext::class)->setArgument('$passwordHasher', class_exists(NativePasswordHasher::class) ? 'security.user_password_encoder' : 'security.user_password_hasher');

        $messengerConfig = [
            'default_bus' => 'messenger.bus.default',
            'buses' => [
                'messenger.bus.default' => ['default_middleware' => 'allow_no_handlers'],
            ],
        ];

        // Symfony 5.4+
        if (class_exists(SessionFactory::class) && !class_exists(PingWebhookMessageHandler::class)) {
            $messengerConfig['reset_on_message'] = true;
        }

        // This class is introduced in Symfony 6.4 just using it to use the new configuration and to avoid unnecessary deprecations
        // Fixes framework configuration for 2.7
        if (class_exists(PingWebhookMessageHandler::class)) {
            $config = [
                'secret' => 'dunglas.fr',
                'validation' => ['enable_attributes' => true, 'email_validation_mode' => 'html5'],
                'serializer' => ['enable_attributes' => true],
                'test' => null,
                'session' => ['cookie_secure' => true, 'cookie_samesite' => 'lax', 'handler_id' => null, 'storage_factory_id' => 'session.storage.factory.mock_file'],
                'profiler' => [
                    'enabled' => true,
                    'collect' => false,
                ],
                'php_errors' => ['log' => true],
                'messenger' => $messengerConfig,
                'router' => ['utf8' => true],
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'uid' => ['default_uuid_version' => 7, 'time_based_uuid_version' => 7],
            ];
        } else {
            $config = [
                'secret' => 'dunglas.fr',
                'validation' => ['enable_annotations' => true],
                'serializer' => ['enable_annotations' => true],
                'test' => null,
                'session' => class_exists(SessionFactory::class) ? ['handler_id' => null, 'storage_factory_id' => 'session.storage.factory.mock_file'] : ['storage_id' => 'session.storage.mock_file'],
                'profiler' => [
                    'enabled' => true,
                    'collect' => false,
                ],
                'messenger' => $messengerConfig,
                'router' => ['utf8' => true],
            ];
        }
        $c->prependExtensionConfig('framework', $config);

        $alg = class_exists(NativePasswordHasher::class, false) || class_exists('Symfony\Component\Security\Core\Encoder\NativePasswordEncoder') ? 'auto' : 'bcrypt';
        $securityConfig = [
            class_exists(NativePasswordHasher::class) ? 'password_hashers' : 'encoders' => [
                User::class => $alg,
                UserDocument::class => $alg,
                // Don't use plaintext in production!
                UserInterface::class => 'plaintext',
            ],
            'providers' => [
                'chain_provider' => [
                    'chain' => [
                        'providers' => ['in_memory', 'entity'],
                    ],
                ],
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'dunglas' => ['password' => 'kevin', 'roles' => 'ROLE_USER'],
                            'admin' => ['password' => 'kitten', 'roles' => 'ROLE_ADMIN'],
                        ],
                    ],
                ],
                'entity' => [
                    'entity' => [
                        'class' => User::class,
                        'property' => 'email',
                    ],
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'default' => [
                    'provider' => 'chain_provider',
                    'stateless' => true,
                    'http_basic' => null,
                    'anonymous' => null,
                    'entry_point' => 'app.security.authentication_entrypoint',
                ],
            ],
            'access_control' => [
                ['path' => '^/', 'role' => interface_exists(AccessDecisionStrategyInterface::class) ? 'PUBLIC_ACCESS' : 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ],
        ];

        if (!class_exists(SymfonyCoreUser::class)) {
            $securityConfig['role_hierarchy'] = [
                'ROLE_ADMIN' => ['ROLE_USER'],
            ];
            unset($securityConfig['firewalls']['default']['anonymous']);
            $securityConfig['firewalls']['default']['http_basic'] = [
                'realm' => 'Secured Area',
            ];
        }

        if (class_exists(NativePasswordHasher::class) && !class_exists(PingWebhookMessageHandler::class)) {
            $securityConfig['enable_authenticator_manager'] = true;
            unset($securityConfig['firewalls']['default']['anonymous']);
        }

        $c->prependExtensionConfig('security', $securityConfig);

        if (class_exists(DoctrineMongoDBBundle::class)) {
            $c->prependExtensionConfig('doctrine_mongodb', [
                'connections' => [
                    'default' => null,
                ],
                'document_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                    ],
                ],
            ]);
        }

        $twigConfig = ['strict_variables' => '%kernel.debug%'];
        if (interface_exists(ErrorRendererInterface::class)) {
            $twigConfig['exception_controller'] = null;
        }
        $c->prependExtensionConfig('twig', $twigConfig);

        $doctrineConfig = [];
        // @phpstan-ignore-next-line
        if (method_exists(EntityManagerConfig::class, 'getReportFieldsWhereDeclared')) {
            $doctrineConfig['orm']['report_fields_where_declared'] = true;
        }
        // @phpstan-ignore-next-line
        if (method_exists(OrmConfig::class, 'enableLazyGhostObjects')) {
            $doctrineConfig['orm']['enable_lazy_ghost_objects'] = true;
        }
        if (!empty($doctrineConfig)) {
            $c->prependExtensionConfig('doctrine', $doctrineConfig);
        }

        if (class_exists(NelmioApiDocBundle::class)) {
            $c->prependExtensionConfig('nelmio_api_doc', [
                'sandbox' => [
                    'accept_type' => 'application/json',
                    'body_format' => [
                        'formats' => ['json'],
                        'default_format' => 'json',
                    ],
                    'request_format' => [
                        'formats' => ['json' => 'application/json'],
                    ],
                ],
            ]);
            $c->prependExtensionConfig('api_platform', ['enable_nelmio_api_doc' => true]);
        }

        $metadataBackwardCompatibilityLayer = (bool) ($_SERVER['METADATA_BACKWARD_COMPATIBILITY_LAYER'] ?? false);
        $c->prependExtensionConfig('api_platform', ['metadata_backward_compatibility_layer' => $metadataBackwardCompatibilityLayer]);

        if ($metadataBackwardCompatibilityLayer) {
            $loader->load(__DIR__.'/config/config_metadata_backward_compatibility_layer.yml');
            $c->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_legacy'],
                ],
            ]);

            if ('mongodb' === $this->environment) {
                $c->prependExtensionConfig('api_platform', [
                    'mapping' => [
                        'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_legacy_odm'],
                    ],
                ]);

                return;
            }

            $c->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_legacy_orm'],
                ],
            ]);

            return;
        }

        $loader->load(__DIR__.'/config/config_v3.yml');

        if ('elasticsearch' === $this->environment) {
            return;
        }

        $c->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_v3'],
            ],
        ]);

        if ('mongodb' === $this->environment) {
            $c->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_v3_odm'],
                ],
            ]);

            return;
        }

        $c->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_v3_orm'],
            ],
        ]);
    }
}
