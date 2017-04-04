<?php

namespace PhpSolution\AppCacheBundle\DependencyInjection;

use Doctrine\Common\Cache\RedisCache;
use PhpSolution\AppCacheBundle\Cacheable\SessionHandler;
use PhpSolution\AppCacheBundle\Cacheable\SwiftMailerSpool;
use PhpSolution\AppCacheBundle\Utils\ClientFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Validator\Mapping\Cache\DoctrineCache;

/**
 * Class AppCacheExtension
 *
 * @package PhpSolution\AppCacheBundle\DependencyInjection
 */
class AppCacheExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerRedisConnection($config['redis_clients'], $container);
        $this->registerFrameworkBundleCaches($config['framework'], $container);
        if (isset($config['swiftmailer'])) {
            $this->registerSwiftmailerSpool($config['swiftmailer'], $container);
        }
        if (isset($config['session'])) {
            $this->registerSessionHandler($config['session'], $container);
        }
        if (isset($config['doctrine']['providers'])) {
            $this->registerDoctrineCacheProviders($config['doctrine']['providers'], $container);
        }
        if (isset($config['doctrine']['cache'])) {
            $this->registerDoctrineCache($config['doctrine']['cache'], $container);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerRedisConnection(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $key => $clientConfig) {
            $def = (new Definition($clientConfig['class']))
                ->setPublic($clientConfig['public'])
                ->setFactory([ClientFactory::class, 'createRedisClient'])
                ->setArguments([$clientConfig['dsn'], (array) $clientConfig['options']]);
            $container->setDefinition($clientConfig['id'] ?: 'app_cache.redis_clients.' . $key, $def);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerFrameworkBundleCaches(array $config, ContainerBuilder $container): void
    {
        if ($config['validator']['enabled']) {
            $this->addDoctrineCacheProviderDef(
                $container,
                'app_cache.framework.validator_mapping_cache.driver',
                RedisCache::class,
                $config['validator']['client_id'],
                $config['validator']['namespace']
            );
            $def = (new Definition(DoctrineCache::class))
                ->setPublic(false)
                ->setArguments([new Reference('app_cache.framework.validator_mapping_cache.driver')]);
            $container->setDefinition('app_cache.framework.validator_mapping_cache', $def);
        }

        if ($config['serializer']['enabled']) {
            $this->addDoctrineCacheProviderDef(
                $container,
                'app_cache.framework.serializer_mapping_cache',
                RedisCache::class,
                $config['serializer']['client_id'],
                $config['serializer']['namespace']
            );
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerSwiftmailerSpool(array $config, ContainerBuilder $container): void
    {
        if ($config['enabled']) {
            $definition = (new Definition(SwiftMailerSpool::class))
                ->setPublic(false)
                ->setArguments([
                    new Reference($config['client_id']),
                    $config['cache_key']
                ]);
            $container->setDefinition('app_cache.cacheable.swiftmailer_spool', $definition);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerSessionHandler(array $config, ContainerBuilder $container): void
    {
        if ($config['enabled']) {
            $definition = (new Definition(SessionHandler::class))
                ->setPublic(false)
                ->setArguments([
                    new Reference($config['client_id']),
                    $config['ttl'],
                    $config['cache_key']
                ]);
            $container->setDefinition('app_cache.cacheable.session_handler', $definition);
        }
    }

    /**
     * @param array            $providerConfigs
     * @param ContainerBuilder $container
     */
    private function registerDoctrineCacheProviders(array $providerConfigs, ContainerBuilder $container): void
    {
        foreach ($providerConfigs as $conf) {
            $this->addDoctrineCacheProviderDef($container, $conf['id'], $conf['class'], $conf['client_id'], $conf['namespace']);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerDoctrineCache(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $name => $cacheConfig) {
            if ('second_level_cache' === $name) {
                $name = 'second_level_cache.region_cache_driver';
            }
            foreach ($cacheConfig['entity_managers'] as $em) {
                $container->setAlias(sprintf('doctrine.orm.%s_%s', $em, $name), $cacheConfig['provider_id']);
            }
            foreach ($cacheConfig['document_managers'] as $dm) {
                $container->setAlias(sprintf('doctrine.orm.%s_%s', $dm, $name), $cacheConfig['provider_id']);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     * @param string           $class
     * @param string           $clientId
     * @param string           $namespace
     */
    private function addDoctrineCacheProviderDef(ContainerBuilder $container, string $id, string $class, string $clientId, string $namespace): void
    {
        $providerDef = (new Definition($class))
            ->setPublic(false)
            ->addMethodCall('setRedis', [new Reference($clientId)]);
        if (!empty($namespace)) {
            $providerDef->addMethodCall('setNamespace', [$namespace]);
        }
        $container->setDefinition($id, $providerDef);
    }
}
