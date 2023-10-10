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

use InvalidArgumentException;
use Socket;

abstract class ClamAV
{
    /**
     * @var int
     */
    public const CLAMAV_MAX = 20000;

    /**
     * Create a new connection based on the DSN string.
     */
    public static function createFromDSN(string $dsn): Pipe|Network
    {
        if (\str_starts_with($dsn, 'unix://')) {
            return new Pipe(\substr($dsn, 7));
        }

        if (\str_starts_with($dsn, 'tcp://')) {
            [$host, $port] = \explode(':', \substr($dsn, 6));

            return new Network($host, (int)$port);
        }

        throw new InvalidArgumentException("Unsupported scheme in DSN");
    }

    /**
     * Returns a remote socket.
     */
    abstract protected function getSocket(): Socket;

    /**
     * Send a given command to ClamAV.
     */
    private function sendCommand(string $command): ?string
    {
        $return = null;

        $socket = $this->getSocket();

        \socket_send($socket, $command, \strlen($command), 0);
        \socket_recv($socket, $return, self::CLAMAV_MAX, 0);
        \socket_close($socket);

        return \trim($return);
    }

    /**
     * Check if ClamAV is up and responsive.
     */
    public function ping(): bool
    {
        $return = $this->sendCommand('PING');

        return \trim($return) === 'PONG';
    }

    /**
     * Check ClamAV Version.
     */
    public function version(): string
    {
        return \trim($this->sendCommand('VERSION'));
    }

    /**
     * Reload ClamAV virus databases.
     */
    public function reload(): ?string
    {
        return $this->sendCommand('RELOAD');
    }

    /**
     * Shutdown ClamAV and preform a clean exit.
     */
    public function shutdown(): ?string
    {
        return $this->sendCommand('SHUTDOWN');
    }

    /**
     * Scan a file or a directory (recursively) with archive support
     * enabled (if not disabled in clamd.conf). A full path is required.
     *
     * Returns whether the given file/directory is clean (true), or not (false).
     */
    public function fileScanInStream(string $file): bool
    {
        $socket = $this->getSocket();

        $handle = \fopen($file, 'rb');
        $chunkSize = \filesize($file) < 8192 ? \filesize($file) : 8192;
        $command = "zINSTREAM\0";

        \socket_send($socket, $command, \strlen($command), 0);

        while (!\feof($handle)) {
            $data = \fread($handle, $chunkSize);

            if ($data === "") {
                continue;
            }

            $packet = \pack(\sprintf("Na%d", $chunkSize), $chunkSize, $data);
            \socket_send($socket, $packet, $chunkSize + 4, 0);
        }

        \socket_send($socket, \pack("Nx", 0), 5, 0);
        \socket_recv($socket, $out, 20000, 0);
        \socket_close($socket);

        $out = \explode(':', $out);
        $stats = \end($out);

        return \trim($stats) === 'OK';
    }

    /**
     * Scan a file or a directory (recursively) with archive support
     * enabled (if not disabled in clamd.conf). A full path is required.
     *
     * Returns whether the given file/directory is clean (true), or not (false).
     */
    public function fileScan(string $file): bool
    {
        $out = $this->sendCommand('SCAN ' . $file);

        $out = \explode(':', $out);
        $stats = \end($out);

        return \trim($stats) === 'OK';
    }

    /**
     * Scan file or directory (recursively) with archive support
     * enabled, and don't stop the scanning when a virus is found.
     */
    public function continueScan(string $file): array
    {
        $return = [];

        foreach (\explode("\n", \trim($this->sendCommand('CONTSCAN ' . $file))) as $results) {
            [$file, $stats] = \explode(':', $results);
            $return[] = ['file' => $file, 'stats' => \trim($stats)];
        }

        return $return;
    }
}
