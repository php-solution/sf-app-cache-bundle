<?php

namespace PhpSolution\AppCacheBundle\Cacheable;

/**
 * SessionHandler
 */
class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Redis
     */
    private $redisClient;
    /**
     * @var int
     */
    private $ttl;
    /**
     * @var string
     */
    private $prefix;

    /**
     * @param \Redis      $redisClient
     * @param int         $ttl
     * @param null|string $prefix
     */
    public function __construct(\Redis $redisClient, int $ttl, ?string $prefix)
    {
        $this->redisClient = $redisClient;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        $this->redisClient->delete($this->getKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string
    {
        return (string) $this->redisClient->get($this->getKey($sessionId));
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $sessionData): bool
    {
        return (0 < $this->ttl)
            ? $this->redisClient->setex($this->getKey($sessionId), $this->ttl, $sessionData)
            : $this->redisClient->set($this->getKey($sessionId), $sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxLifeTime): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    private function getKey($sessionId): string
    {
        return empty($this->prefix) ? $sessionId : $this->prefix . $sessionId;
    }
}