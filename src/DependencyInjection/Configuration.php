<?php

namespace PhpSolution\AppCacheBundle\DependencyInjection;

use Doctrine\Common\Cache\RedisCache;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 */
class Configuration implements ConfigurationInterface
{
    private const DEFAULT_REDIS_CLIENT = 'app_cache.redis_client.general';

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app_cache');

        $this->addClientsSection($rootNode);
        $this->addFrameworkBundleSection($rootNode);
        $this->addSwiftMailerSection($rootNode);
        $this->addSessionHandlerSection($rootNode);
        $this->addDoctrineSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addClientsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('redis_clients')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->fixXmlConfig('redis_client')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('id')->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                            ->scalarNode('public')->defaultFalse()->end()
                            ->scalarNode('class')->cannotBeEmpty()->defaultValue('Redis')->end()
                            ->scalarNode('dsn')->cannotBeEmpty()->defaultValue('redis://localhost:6379')->end()
                            ->scalarNode('options')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addFrameworkBundleSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('framework')
                    ->children()
                        ->arrayNode('validator')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('client_id')->cannotBeEmpty()->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                                ->scalarNode('namespace')->defaultValue('sf_validator')->end()
                            ->end()
                        ->end()
                        ->arrayNode('serializer')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('client_id')->cannotBeEmpty()->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                                ->scalarNode('namespace')->defaultValue('sf_serializer')->end()
                            ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSwiftMailerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('swiftmailer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('client_id')->cannotBeEmpty()->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                        ->scalarNode('cache_key')->defaultValue('swiftmailer_spool')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSessionHandlerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('session')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('client_id')->cannotBeEmpty()->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                        ->scalarNode('ttl')->defaultValue(86400)->end()
                        ->scalarNode('cache_key')->defaultValue('sfs_')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addDoctrineSection(ArrayNodeDefinition $rootNode): void
    {
        $doctrineNode = $rootNode->children()->arrayNode('doctrine')->canBeUnset();
        // Providers
        $doctrineNode
            ->children()
                ->arrayNode('providers')
                    ->prototype('array')
                    ->fixXmlConfig('provider')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('id')->cannotBeEmpty()->end()
                        ->scalarNode('class')->defaultValue(RedisCache::class)->end()
                        ->scalarNode('namespace')->defaultNull()->end()
                        ->scalarNode('client_id')->cannotBeEmpty()->defaultValue(self::DEFAULT_REDIS_CLIENT)->end()
                    ->end()
                ->end()
            ->end();

        $doctrineCacheNode = $doctrineNode->children()->arrayNode('cache')->canBeUnset();
        foreach (['metadata_cache', 'result_cache', 'query_cache', 'second_level_cache'] as $type) {
            $doctrineCacheNode
                ->children()
                    ->arrayNode($type)
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('provider_id')->isRequired()->end()
                        ->end()
                        ->fixXmlConfig('entity_manager')
                        ->children()
                            ->arrayNode('entity_managers')
                            ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('document_manager')
                        ->children()
                            ->arrayNode('document_managers')
                            ->defaultValue(array())
                                ->beforeNormalization()->ifString()->then(function($v) { return (array) $v; })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
        }
    }
}
