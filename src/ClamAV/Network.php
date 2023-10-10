<?php

/**
 * Utopia PHP Framework
 *
 * @package ClamAV
 *
 * @link https://github.com/utopia-php/framework
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Appwrite\ClamAV;

use RuntimeException;
use Socket;

use const AF_INET;
use const SOCK_STREAM;

class Network extends ClamAV
{
    /**
     * @var string
     */
    public const CLAMAV_HOST = '127.0.0.1';

    /**
     * @var int
     */
    public const CLAMAV_PORT = 3310;

    private string $host;

    private int $port;

    /**
     * Network constructor
     *
     * You need to pass the host address and the port the server.
     */
    public function __construct(string $host = self::CLAMAV_HOST, int $port = self::CLAMAV_PORT)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @inheritDoc
     */
    protected function getSocket(): Socket
    {
        $socket = @\socket_create(AF_INET, SOCK_STREAM, 0);
        $status = @\socket_connect($socket, $this->host, $this->port);

        if (!$status) {
            throw new RuntimeException('Unable to connect to ClamAV server');
        }

        return $socket;
    }
}
