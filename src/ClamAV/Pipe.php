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

use Socket;
use const AF_UNIX;
use const SOCK_STREAM;

class Pipe extends ClamAV
{
    /**
     * @var string
     */
    private const CLAMAV_HOST = '/run/clamav/clamd.sock';

    /**
     * @var string
     */
    private string $pip;

    /**
     * Pipe constructor.
     *
     * This class can be used to connect to local socket.
     * You need to pass the path to the socket pipe.
     *
     * @param string $pip
     */
    public function __construct(string $pip = self::CLAMAV_HOST)
    {
        $this->pip = $pip;
    }

    /**
     * Returns a local socket.
     *
     * @return Socket
     */
    protected function getSocket(): Socket
    {
        $socket = \socket_create(AF_UNIX, SOCK_STREAM, 0);
        \socket_connect($socket, $this->pip);

        return $socket;
    }
}
