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

abstract class ClamAV
{
    /**
     * @var int
     */
    public const CLAMAV_MAX = 20000;

    /**
     * @var int
     */
    private const INSTREAM_CHUNK = 8192;

    /**
     * @return resource
     */
    abstract protected function getSocket();

    /**
     * Send a given command to ClamAV.
     *
     * @param string $command
     * @return string|null
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
     *
     * @return bool
     */
    public function ping(): bool
    {
        $return = $this->sendCommand('PING');

        return \trim($return) === 'PONG';
    }

    /**
     * Check ClamAV Version.
     *
     * @return string
     */
    public function version(): string
    {
        return \trim($this->sendCommand('VERSION'));
    }

    /**
     * Reload ClamAV virus databases.
     *
     * @return string|null
     */
    public function reload(): ?string
    {
        return $this->sendCommand('RELOAD');
    }

    /**
     * Shutdown ClamAV and preform a clean exit.
     *
     * @return string|null
     */
    public function shutdown(): ?string
    {
        return $this->sendCommand('SHUTDOWN');
    }

    /**
     * Scan a file or a directory (recursively) with archive support
     * enabled (if not disabled in clamd.conf). A full path is required.
     *
     * Scan a file by streaming it to ClamAV.
     *
     * @param string $file
     * @return ScanResult
     */
    public function scanInStream(string $file): ScanResult
    {
        $handle = \fopen($file, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open ' . $file . ' for scanning');
        }

        $socket = $this->getSocket();

        try {
            $this->sendAll($socket, "zINSTREAM\0");

            while (!\feof($handle)) {
                $data = \fread($handle, self::INSTREAM_CHUNK);

                if ($data === false) {
                    throw new RuntimeException('Unable to read ' . $file . ' for scanning');
                }

                $length = \strlen($data);

                if ($length === 0) {
                    continue;
                }

                $this->sendAll($socket, \pack('N', $length) . $data);
            }

            $this->sendAll($socket, \pack('N', 0));
            \socket_recv($socket, $out, self::CLAMAV_MAX, 0);
        } finally {
            \fclose($handle);
            \socket_close($socket);
        }

        return ScanResult::fromReply((string) $out);
    }

    /**
     * Whether the given file is clean.
     *
     * A scan that could not be performed is not a clean file, so this answers
     * false for it -- the same answer it gives for a detection, which is why
     * anything that acts on the result should call scanInStream() instead and
     * tell the two apart.
     *
     * @param string $file
     * @return bool
     */
    public function fileScanInStream(string $file): bool
    {
        return $this->scanInStream($file)->isClean();
    }

    /**
     * Write a payload to the socket in full.
     *
     * socket_send() reports how many bytes it accepted and is free to accept
     * fewer than offered. INSTREAM frames each chunk with its own length, so a
     * dropped remainder leaves ClamAV reading file content as the next frame's
     * length prefix -- it answers with a size error rather than a verdict, and
     * the caller cannot tell that apart from an infected file.
     *
     * @param resource $socket
     * @param string $payload
     * @return void
     */
    private function sendAll($socket, string $payload): void
    {
        $total = \strlen($payload);
        $sent = 0;

        while ($sent < $total) {
            $written = \socket_send($socket, \substr($payload, $sent), $total - $sent, 0);

            if ($written === false) {
                throw new RuntimeException(
                    'ClamAV accepted ' . $sent . ' of ' . $total . ' bytes: '
                    . \socket_strerror(\socket_last_error($socket))
                );
            }

            $sent += $written;
        }
    }

    /**
     * Scan a file or a directory (recursively) with archive support
     * enabled (if not disabled in clamd.conf). A full path is required.
     *
     * Returns whether the given file/directory is clean (true), or not (false).
     *
     * @param string $file
     * @return bool
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
     *
     * @param string $file
     * @return array
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
