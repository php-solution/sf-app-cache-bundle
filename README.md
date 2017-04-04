# AppCacheBundle
This bundle allows developer to use Redis as main cache provider on Symfony application. 

Better Performance:
* Bundle composer.json includes `ext-redis` on require part, because [phpredis lib](https://github.com/phpredis/phpredis) allow you better performance using Redis on app.
* For serialization please install and enable php extension [igbinary](https://pecl.php.net/package/igbinary).

## Redis Clients
You can specify Redis Client as service on Symfony DI.

As default new DI Reference will created via factory `\PhpSolution\Utils\ClientFactory::createRedisClient()` and set correct serializer option for \Redis.

Example of configuration:
````
app_cache:
    redis_clients:
        general:
            public: false
            id: 'app_cache.redis_clients.general'
            class: 'Redis'
            dsn: 'redis://localhost:6379/db_name'
            options: ~
````
On app
````
/* @var $client \Redis */
$client = $this->container->get('app_cache.redis_clients.general');
$client->set('key', 'value');
$string = $client->get('key'); // $string === 'value'
````

## Integration with SymfonyFrameworkBundle
### Cache Spool
You can use Redis client service as [SF cache pool provider](http://symfony.com/doc/current/components/cache/cache_pools.html)
 
Example of app_cache bundle configuration:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/data_base_name'
````
Example of FrameworkBundle configuration:
````
framework:
    cache:
        pools:
            cache_pools.redis.general:
                public: false
                default_lifetime: 0
                adapter: 'cache.adapter.redis'
                provider: 'app_cache.redis_clients.general' # Redis client service id
````

### Cache providers
SymfonyFrameworkBundle allows you to cache:
* validation mapper cache (on prod use file_cache)
* serializer mapper cache (on prod use file_cache)
* annotations (you can use php_array, )
* templating (use only for php template engine, [see more](http://symfony.com/doc/current/reference/configuration/framework.html#cache))

##### Use Redis for validation mapper cache:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'
    framework:
        validator:
            enabled: true
            client_id: 'app_cache.redis_clients.general'
            namespace: 'sf_validator'

framework:
    validation:
        enable_annotations: true
        cache: 'app_cache.framework.validator_mapping_cache'
````
##### Use Redis for serializer mapper cache:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'
    framework:
        serializer:
            enabled: true
            client_id: 'app_cache.redis_clients.general'
            namespace: 'sf_serializer'

framework:    
    validation:
        enable_annotations: true
        cache: 'app_cache.framework.validator_mapping_cache'
````

## Swiftmailer Spool
You can use Redis as storage for spool swiftmailer messages.

AppCacheBundle configuration:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/data_base_name'
    swiftmailer:
        enabled: true            
        client_id: 'app_cache.redis_clients.general'
````
SwiftmailerBundle configuration:
````
swiftmailer:
    spool:
        type: 'service'
        id: 'app_cache.cacheable.swiftmailer_spool'
````
You can customize spool service:
````
app_cache:
    swiftmailer:
        enabled: true
        client_id: 'app_cache.redis_clients.general'
        cache_key: 'swiftmailer_spool'
````

## Sessions Handler
You can use Redis as storage for session. 
Bundle includes SessionHandle, on DI `'app_cache.cacheable.session_handler'` which injects \Redis client and stores sessions on \Redis.

Example of AppCacheBundle configuration:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'
    session:
        enabled: true
        client_id: 'app_cache.redis_clients.general'
````
Example of FrameworkBundle configuration:
````    
    framework:
        session:
            handler_id: 'app_cache.cacheable.session_handler'
````
You can customize Session Handler:
````
app_cache:
    session:
        enabled: true
        client_id: 'app_cache.redis_clients.general'
        ttl: 86400
        cache_key: 'sfs_'
````

## Doctrine Cache Providers
You can specify services as Doctrine cache providers. Example of configurations:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'
    doctrine:
        providers:
            general:
                id: 'app_cache.doctrine_providers.redis'
                class: 'Doctrine\Common\Cache\RedisCache'
                client_id: 'app_cache.redis_clients.general'
                namespace: 'doctrine'
````

### Correct Doctrine Cache Driver configuration
Correct configuration for doctrine:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'
    doctrine:
        providers:
            general:
                id: 'app_cache.doctrine_providers.redis'
                client_id: 'app_cache.redis_clients.general'
                namespace: 'doctrine'            

parameters:
    doctrine_cache.metadata_cache_driver: {type: 'service', id: 'app_cache.doctrine_providers.redis'}
    doctrine_cache.query_cache_driver: {type: 'service', id: 'app_cache.doctrine_providers.redis'}
    doctrine_cache.result_cache_driver: {type: 'service', id: 'app_cache.doctrine_providers.redis'}
    doctrine_cache.slc_driver: {type: 'service', id: 'app_cache.doctrine_providers.redis'}

doctrine:        
    orm:
        entity_managers:
            default:
                metadata_cache_driver: '%doctrine_cache.metadata_cache_driver%'
                query_cache_driver: '%doctrine_cache.query_cache_driver%'
                result_cache_driver: '%doctrine_cache.result_cache_driver%'
                second_level_cache:
                    region_cache_driver: '%doctrine_cache.slc_driver%'
                    regions:
                        concurrent_entity_region:
                            type: 'filelock'
                            cache_driver: '%doctrine_cache.slc_driver%'
                        entity_region:
                             lifetime: 0
                             cache_driver: '%doctrine_cache.slc_driver%'
````

### Doctrine Cache Driver configuration (ONLY FOR BASIC USAGE!!!)
This Bundle allows you to specify cache provider for:
* metadata_cache
* result_cache
* query_cache
* second_level_cache

This functionality works like on [SncRedisBundle](https://github.com/snc/SncRedisBundle/blob/master/Resources/doc/index.md#doctrine-caching) and assign Doctrine cache provider automatically, 
but use only alias for cache provider, not creates separate provider.  

Example of configuration:
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            dsn: 'redis://localhost:6379/db_name'        
    doctrine:
        providers:
            general:
                id: 'app_cache.doctrine_providers.redis'
                client_id: 'app_cache.redis_clients.general'
        cache:
            metadata_cache:
                provider_id: 'app_cache.doctrine_providers.redis'
                entity_managers: ['default']
````
Bundle Extension create new alias for services (patter name: `doctrine.orm.%s_%s`) using as cache_driver.

For example `doctrine.orm.default_metadata_cache` - service name for `metadata_cache` for `default` entity manager.

Use this feature only for basic configuration of Doctrine, because:
* you cannot rewrite for env's cache driver params
* you cannot specify `second_level_cache` regions parameters
* this bundle must be specified on AppKernel after DoctrineBundle 

## Twig cache
Twig use Filesystem cache as provider for cache templates.
You must use Filesystem cache (not Redis and other provider) and for best performance please configure PHP opcache.

Additional info about Twig cache on [link](https://github.com/twigphp/Twig/commit/87e27b6a26fd477a407bf98b4c7af04455437f6a)


## Full Default Configuration
````
app_cache:
    redis_clients:
        general:
            id: 'app_cache.redis_clients.general'
            class: 'Redis'
            dsn: 'redis://localhost:6379/db_name'
            options: ~
    framework:
        validator:
            enabled: true
            client_id: 'app_cache.redis_clients.general'
            namespace: 'sf_validator'
        serializer:
            enabled: true
            client_id: 'app_cache.redis_clients.general'
            namespace: 'sf_serializer'
    swiftmailer:
        enabled: true
        client_id: 'app_cache.redis_clients.general'
        cache_key: 'swiftmailer_spool'
    session:
        enabled: true
        client_id: 'app_cache.redis_clients.general'
        ttl: 86400
        cache_key: 'sfs_'
    doctrine:
        providers:
            general:
                id: 'app_cache.doctrine_providers.redis'
                class: 'Doctrine\Common\Cache\RedisCache'
                client_id: 'app_cache.redis_clients.general'
                namespace: 'doctrine'
        cache:
            metadata_cache:
                provider_id: 'app_cache.doctrine_providers.redis'
                entity_managers: ['default']
                document_managers: ['default']
            result_cache:
                provider_id: 'app_cache.doctrine_providers.redis'
                entity_managers: ['default']
                document_managers: ['default']
            query_cache:
                provider_id: 'app_cache.doctrine_providers.redis'
                entity_managers: ['default']
                document_managers: ['default']
            second_level_cache:
                provider_id: 'app_cache.doctrine_providers.redis'
                entity_managers: ['default']
                document_managers: ['default']
````