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
use PHPUnit\Framework\TestCase;

/**
 * INSTREAM sends the file as length-prefixed frames. What ClamAV scans is
 * whatever those frames spell out, which is not necessarily what is on disk --
 * and a clean verdict cannot tell the difference, so these assert the bytes.
 *
 * Every other test in this suite scans a file smaller than one chunk, so the
 * final-partial-chunk path they all share is never exercised there.
 */
class InStreamFramingTest extends TestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        if ($this->file !== '' && \file_exists($this->file)) {
            \unlink($this->file);
        }
        $this->file = '';
    }

    public function testAFileOfExactlyOneChunkIsSentVerbatim(): void
    {
        $this->assertStreamedBytesMatchTheFile(8192);
    }

    public function testAFileEndingMidChunkIsSentVerbatim(): void
    {
        $this->assertStreamedBytesMatchTheFile(8192 + 1000);
    }

    public function testAFileSmallerThanOneChunkIsSentVerbatim(): void
    {
        $this->assertStreamedBytesMatchTheFile(1000);
    }

    public function testTheStreamIsTerminatedByExactlyFourZeroBytes(): void
    {
        $subject = $this->subject(8192 + 1000);
        $subject->fileScanInStream($this->file);

        $wire = $this->drain($subject->peer);
        $offset = \strlen("zINSTREAM\0");

        while (true) {
            $length = \unpack('N', \substr($wire, $offset, 4))[1];
            $offset += 4;

            if ($length === 0) {
                break;
            }

            $offset += $length;
        }

        self::assertSame(
            \strlen($wire),
            $offset,
            'trailing bytes after the terminator become the first bytes of the next command'
        );
    }

    private function assertStreamedBytesMatchTheFile(int $size): void
    {
        $subject = $this->subject($size);
        $expected = (string) \file_get_contents($this->file);

        $subject->fileScanInStream($this->file);

        $wire = $this->drain($subject->peer);

        self::assertStringStartsWith("zINSTREAM\0", $wire, 'the stream must open with the INSTREAM command');

        $offset = \strlen("zINSTREAM\0");
        $scanned = '';

        while (true) {
            $header = \substr($wire, $offset, 4);
            self::assertSame(4, \strlen($header), 'the stream ended without a terminating frame');

            $length = \unpack('N', $header)[1];
            $offset += 4;

            if ($length === 0) {
                break;
            }

            $frame = \substr($wire, $offset, $length);
            self::assertSame($length, \strlen($frame), 'a frame declared more bytes than it carried');
            $scanned .= $frame;
            $offset += $length;
        }

        self::assertSame(
            \strlen($expected),
            \strlen($scanned),
            'ClamAV was handed a different number of bytes than the file holds'
        );
        self::assertSame($expected, $scanned, 'ClamAV was handed bytes the file does not contain');
    }

    private function subject(int $size): RecordingClamAV
    {
        $this->file = (string) \tempnam(\sys_get_temp_dir(), 'instream');
        \file_put_contents($this->file, \random_bytes($size));

        return new RecordingClamAV();
    }

    /**
     * @param resource $socket
     */
    private function drain($socket): string
    {
        $wire = '';

        while (true) {
            $chunk = @\socket_read($socket, 65536, PHP_BINARY_READ);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $wire .= $chunk;
        }

        return $wire;
    }
}
