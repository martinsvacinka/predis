<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Session;

use Predis\ClientInterface;

/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 *
 * This class is mostly intended for PHP 5.4 but it can be used under PHP 5.3
 * provided that a polyfill for `SessionHandlerInterface` is defined by either
 * you or an external package such as `symfony/http-foundation`.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Handler implements \SessionHandlerInterface
{
    protected $client;
    protected $ttl;

    /**
     * @param ClientInterface $client  Fully initialized client instance.
     * @param array           $options Session handler options.
     */
    public function __construct(ClientInterface $client, array $options = array())
    {
        $this->client = $client;

        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = ini_get('session.gc_maxlifetime');
        }
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register()
    {
        session_set_save_handler($this, true);
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $session_id): bool
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int|false
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id): string
    {
        if ($data = $this->client->get($session_id)) {
            return $data;
        }

        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data): bool
    {
        $this->client->setex($session_id, $this->ttl, $session_data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($session_id): bool
    {
        $this->client->del($session_id);

        return true;
    }

    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
}
