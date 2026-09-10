<?php

/**
 * Utopia PHP Framework
 *
 * @package ClamAV
 *
 * @link https://github.com/utopia-php/framework
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Appwrite\ClamAV\Tests;

use Appwrite\ClamAV\ClamAV;

/**
 * Stands in for the daemon over a real socket pair, so the bytes the subject
 * writes can be read back and checked. The verdict is queued before the scan
 * runs, because nothing here is going to answer while the scan is in progress.
 */
class RecordingClamAV extends ClamAV
{
    /**
     * @var resource
     */
    public $peer;

    /**
     * @var resource
     */
    private $socket;

    public function __construct(string $reply = "stream: OK\0")
    {
        $pair = [];
        \socket_create_pair(\AF_UNIX, \SOCK_STREAM, 0, $pair);

        [$this->socket, $this->peer] = $pair;

        foreach ([$this->socket, $this->peer] as $socket) {
            \socket_set_option($socket, \SOL_SOCKET, \SO_SNDBUF, 1048576);
            \socket_set_option($socket, \SOL_SOCKET, \SO_RCVBUF, 1048576);
        }

        \socket_write($this->peer, $reply);
        \socket_set_nonblock($this->peer);
    }

    /**
     * @return resource
     */
    protected function getSocket()
    {
        return $this->socket;
    }
}
