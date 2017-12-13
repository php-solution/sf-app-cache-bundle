<?php

namespace PhpSolution\AppCacheBundle\Utils;

use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * ClientFactory
 */
class ClientFactory
{
    /**
     * @param string $dsn
     * @param array  $options
     *
     * @return \Redis
     */
    public static function createRedisClient(string $dsn, array $options = []): \Redis
    {
        $result = RedisAdapter::createConnection($dsn, $options);
        if (!$result instanceof \Redis) {
            throw new \InvalidArgumentException();
        }

        if (!array_key_exists(\Redis::OPT_SERIALIZER, $options)) {
            $serializerOption = defined('Redis::SERIALIZER_IGBINARY') && extension_loaded('igbinary')
                ? \Redis::SERIALIZER_IGBINARY
                : \Redis::SERIALIZER_PHP;
            $result->setOption(\Redis::OPT_SERIALIZER, $serializerOption);
        }

        return $result;
    }
}