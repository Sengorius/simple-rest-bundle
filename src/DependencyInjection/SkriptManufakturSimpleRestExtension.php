<?php

namespace SkriptManufaktur\SimpleRestBundle\DependencyInjection;

use Doctrine\ORM\EntityManager;
use Exception;
use SkriptManufaktur\SimpleRestBundle\Component\AbstractApiControllerFactory;
use SkriptManufaktur\SimpleRestBundle\Component\AbstractApiHandlerFactory;
use SkriptManufaktur\SimpleRestBundle\Component\ApiBusWrapper;
use SkriptManufaktur\SimpleRestBundle\Component\ApiFilterService;
use SkriptManufaktur\SimpleRestBundle\Component\EntityIdDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Component\EntityUuidDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Component\LegacyEntityIdDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Component\LegacyEntityUuidDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Listener\ApiResponseListener;
use SkriptManufaktur\SimpleRestBundle\Listener\RequestListener;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingMiddleware;
use SkriptManufaktur\SimpleRestBundle\Voter\RoleService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Class SkriptManufakturSimpleRestExtension
 */
class SkriptManufakturSimpleRestExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load the configuration
        $configuration = $this->processConfiguration(new Configuration(), $configs);

        // register the simple services
        $container->setDefinition(
            ApiBusWrapper::class,
            (new Definition(ApiBusWrapper::class, [new Reference(MessageBusInterface::class)]))
        );
        $container->setAlias('skriptmanufaktur.simple_rest.component.api_bus_wrapper', ApiBusWrapper::class);

        $container->setDefinition(ApiFilterService::class, (new Definition(ApiFilterService::class)));
        $container->setAlias('skriptmanufaktur.simple_rest.component.api_filter_service', ApiBusWrapper::class);

        // register all our provider interfaces with a tag
        $abstractApiServices = [
            new Reference('validator'),
            new Reference('serializer'),
            new Reference(ApiBusWrapper::class),
            new Reference(ApiFilterService::class),
        ];

        $container->registerForAutoconfiguration(AbstractApiHandlerFactory::class)
            ->addTag('skriptmanufaktur.simple_rest.abstract.api_handler_factory')
            ->addMethodCall('setServices', $abstractApiServices)
        ;

        $container->registerForAutoconfiguration(AbstractApiControllerFactory::class)
            ->addTag('skriptmanufaktur.simple_rest.abstract.api_constroller_factory')
            ->addTag('controller.service_arguments')
            ->addMethodCall('setServices', $abstractApiServices)
        ;

        // add serializer capabilities for Doctrine, if Doctrine is enabled
        if (class_exists(EntityManager::class)) {
            $idNormalizer = EntityIdDenormalizer::class;
            $uuidNormalizer = EntityUuidDenormalizer::class;

            if (version_compare(Kernel::VERSION, '6.1.0', '<')) {
                $idNormalizer = LegacyEntityIdDenormalizer::class;
                $uuidNormalizer = LegacyEntityUuidDenormalizer::class;
            }

            $container->setDefinition(
                $idNormalizer,
                (new Definition($idNormalizer, [new Reference('doctrine')]))
                    ->addTag('serializer.normalizer')
            );
            $container->setAlias('skriptmanufaktur.simple_rest.component.entity_id_denormalizer', $idNormalizer);

            $container->setDefinition(
                $uuidNormalizer,
                (new Definition($uuidNormalizer, [new Reference('doctrine')]))
                    ->addTag('serializer.normalizer')
            );
            $container->setAlias('skriptmanufaktur.simple_rest.component.entity_uuid_denormalizer', $uuidNormalizer);
        }

        // add voting capabilities, if security is installed
        if (class_exists(AuthorizationChecker::class)) {
            $container->setDefinition(
                GrantingMiddleware::class,
                (new Definition(GrantingMiddleware::class, [
                    new Reference('security.authorization_checker'),
                    $configuration['granting_middleware_throws'],
                ]))
            );
            $container->setAlias('skriptmanufaktur.simple_rest.voter.granting_middleware', GrantingMiddleware::class);

            $container->setDefinition(
                RoleService::class,
                (new Definition(RoleService::class, [new Parameter('security.role_hierarchy.roles')]))
            );
            $container->setAlias('skriptmanufaktur.simple_rest.voter.role_service', RoleService::class);
        }

        // add listeners
        $container->setDefinition(
            RequestListener::class,
            (new Definition(RequestListener::class, [$configuration['default_requesting_origin']]))
                ->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onRequestProceed'])
        );
        $container->setAlias('skriptmanufaktur.simple_rest.listener.request_params', RequestListener::class);

        $container->setDefinition(
            ApiResponseListener::class,
            (new Definition(ApiResponseListener::class, [$configuration['firewall_names']]))
                ->addTag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'formatException', 'priority' => -10])
                ->addTag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'testApiResponseType', 'priority' => 100])
                ->addTag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'addFlashbagMessages', 'priority' => 90])
        );
        $container->setAlias('skriptmanufaktur.simple_rest.listener.api_response', ApiResponseListener::class);
    }
}
