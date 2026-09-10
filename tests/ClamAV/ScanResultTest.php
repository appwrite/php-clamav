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

use Appwrite\ClamAV\ScanResult;
use PHPUnit\Framework\TestCase;

/**
 * A scan that could not be performed is not a detection. Conflating the two
 * tells someone their file is infected because the scanner was busy, and
 * callers act on that: appwrite/appwrite deletes the upload and answers 403
 * "The uploaded file is invalid".
 */
class ScanResultTest extends TestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        if ($this->file !== '' && \file_exists($this->file)) {
            \unlink($this->file);
        }
        $this->file = '';
    }

    public function testACleanReplyIsClean(): void
    {
        $result = ScanResult::fromReply('stream: OK');

        self::assertTrue($result->isClean());
        self::assertFalse($result->isInfected());
        self::assertFalse($result->hasFailed());
        self::assertNull($result->getSignature());
    }

    public function testADetectionCarriesItsSignature(): void
    {
        $result = ScanResult::fromReply('stream: Eicar-Signature FOUND');

        self::assertTrue($result->isInfected());
        self::assertFalse($result->isClean());
        self::assertFalse($result->hasFailed());
        self::assertSame('Eicar-Signature', $result->getSignature());
    }

    /**
     * The reply ClamAV sends when the stream is longer than StreamMaxLength.
     * Nothing was scanned, so nothing was found.
     */
    public function testASizeRefusalIsAFailureAndNotADetection(): void
    {
        $result = ScanResult::fromReply('INSTREAM size limit exceeded. ERROR');

        self::assertTrue($result->hasFailed());
        self::assertFalse($result->isInfected(), 'a refusal to scan was reported as a detection');
        self::assertFalse($result->isClean());
    }

    public function testATruncatedReplyIsAFailureAndNotCleanliness(): void
    {
        $result = ScanResult::fromReply('');

        self::assertTrue($result->hasFailed());
        self::assertFalse($result->isClean(), 'an empty reply was reported as a clean file');
        self::assertFalse($result->isInfected());
    }

    public function testAnUnrecognisedReplyIsAFailure(): void
    {
        $result = ScanResult::fromReply('stream: something nobody has seen before');

        self::assertTrue($result->hasFailed());
        self::assertFalse($result->isClean());
        self::assertFalse($result->isInfected());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function replies(): array
    {
        return [
            'clean' => ["stream: OK\0", ScanResult::CLEAN],
            'detection' => ["stream: Test.Sig FOUND\0", ScanResult::INFECTED],
            'size refusal' => ["INSTREAM size limit exceeded. ERROR\0", ScanResult::FAILED],
        ];
    }

    /**
     * @dataProvider replies
     */
    public function testTheScannerClassifiesWhatTheDaemonActuallySent(string $reply, string $expected): void
    {
        $subject = new RecordingClamAV($reply);

        $this->file = (string) \tempnam(\sys_get_temp_dir(), 'scan');
        \file_put_contents($this->file, 'payload');

        self::assertSame($expected, $subject->scanInStream($this->file)->getStatus());
    }

    public function testTheBooleanApiStillReportsOnlyCleanliness(): void
    {
        $this->file = (string) \tempnam(\sys_get_temp_dir(), 'scan');
        \file_put_contents($this->file, 'payload');

        self::assertTrue((new RecordingClamAV("stream: OK\0"))->fileScanInStream($this->file));
        self::assertFalse((new RecordingClamAV("stream: Test.Sig FOUND\0"))->fileScanInStream($this->file));
        self::assertFalse((new RecordingClamAV("INSTREAM size limit exceeded. ERROR\0"))->fileScanInStream($this->file));
    }
}
