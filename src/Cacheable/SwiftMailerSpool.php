<?php
namespace PhpSolution\AppCacheBundle\Cacheable;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class SwiftMailerSpool
 *
 * @package PhpSolution\AppCacheBundle\Cacheable
 */
class SwiftMailerSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var \Redis
     */
    private $redisClient;
    /**
     * @var string
     */
    private $cacheKey;
    /**
     * @var bool
     */
    private $started = false;

    /**
     * SwiftMailerSpool constructor.
     *
     * @param \Redis $redisClient
     * @param string $cacheKey
     */
    public function __construct(\Redis $redisClient, string $cacheKey)
    {
        $this->redisClient = $redisClient;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start(): void
    {
        $this->started = true;
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop(): void
    {
        $this->started = false;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return bool
     */
    public function queueMessage(\Swift_Mime_Message $message): bool
    {
        $this->redisClient->rPush($this->cacheKey, serialize($message));

        return true;
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param \Swift_Transport $transport        A transport instance
     * @param string[]        $failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null): int
    {
        if (!$this->redisClient->lLen($this->cacheKey)) {
            return 0;
        }
        if (!$transport->isStarted()) {
            $transport->start();
        }

        $failedRecipients = (array) $failedRecipients;
        $count = 0;
        $time = time();

        while (($message = unserialize($this->redisClient->lpop($this->cacheKey)))) {
            $count += $transport->send($message, $failedRecipients);

            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }
            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }
}