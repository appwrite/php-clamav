<?php

namespace Appwrite\ClamAV;

use RuntimeException;
use function socket_connect;
use function socket_create;

class Network extends ClamAV
{
    private const CLAMAV_HOST = '127.0.0.1';
    private const CLAMAV_PORT = 3310;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * Network constructor
     *
     * You need to pass the host address and the port the the server
     *
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host = self::CLAMAV_HOST, int $port = self::CLAMAV_PORT)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @return resource
     * @throws RuntimeException
     */
    protected function getSocket()
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, 0);
        $status = @socket_connect($socket, $this->host, $this->port);

        if (!$status) {
            throw new RuntimeException('Unable to connect to ClamAV server');
        }
        return $socket;
    }
}
