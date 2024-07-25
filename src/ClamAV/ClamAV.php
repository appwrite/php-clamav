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

abstract class ClamAV
{
    /**
     * @var int
     */
    public const CLAMAV_MAX = 20000;

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
     * Returns whether the given file/directory is clean (true), or not (false).
     *
     * @param string $file
     * @return bool
     */
    public function fileScanInStream(string $file): bool
    {
        $file_handler = fopen($file, 'r');
        $scanner_handler = socket_export_stream($this->getSocket());

        // Push to the ClamAV socket.
        $bytes = filesize($file);
        fwrite($scanner_handler, "zINSTREAM\0");
        fwrite($scanner_handler, pack("N", $bytes));
        stream_copy_to_stream($file_handler, $scanner_handler);

        // Send a zero-length block to indicate that we're done sending file data.
        fwrite($scanner_handler, pack("N", 0));

        // Request a response from the service.
        $response = trim(fgets($scanner_handler));

        fclose($scanner_handler);

        return preg_match('/^stream: OK$/', $response);
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
