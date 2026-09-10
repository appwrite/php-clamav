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

/**
 * The outcome of a scan.
 *
 * ClamAV answers a scan with one of three things: the content is clean, the
 * content matched a signature, or it could not be scanned at all -- the stream
 * was longer than StreamMaxLength, the reply was truncated, the engine was busy.
 * A caller that only learns "not clean" has to treat the third as the second,
 * which means telling someone their file is infected because the scanner was
 * unavailable.
 */
final class ScanResult
{
    public const CLEAN = 'clean';

    public const INFECTED = 'infected';

    public const FAILED = 'failed';

    private string $status;

    private string $reply;

    private ?string $signature;

    private function __construct(string $status, string $reply, ?string $signature = null)
    {
        $this->status = $status;
        $this->reply = $reply;
        $this->signature = $signature;
    }

    /**
     * Classify a raw clamd reply.
     *
     * INSTREAM answers `stream: OK`, `stream: <signature> FOUND`, or something
     * ending in `ERROR` such as `INSTREAM size limit exceeded. ERROR`. Anything
     * else -- an empty read, a truncated line -- is a scan that did not happen,
     * not a clean file.
     */
    public static function fromReply(string $reply): self
    {
        $trimmed = \trim($reply);

        if ($trimmed === '') {
            return new self(self::FAILED, $trimmed);
        }

        if (\substr($trimmed, -3) === ' OK' || $trimmed === 'OK') {
            return new self(self::CLEAN, $trimmed);
        }

        if (\substr($trimmed, -6) === ' FOUND') {
            $signature = \trim(\substr($trimmed, 0, -6));

            $colon = \strrpos($signature, ':');
            if ($colon !== false) {
                $signature = \trim(\substr($signature, $colon + 1));
            }

            return new self(self::INFECTED, $trimmed, $signature === '' ? null : $signature);
        }

        return new self(self::FAILED, $trimmed);
    }

    public static function failed(string $reason): self
    {
        return new self(self::FAILED, $reason);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * The signature that matched, when one did.
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * The raw reply, for logging a failure that is worth understanding.
     */
    public function getReply(): string
    {
        return $this->reply;
    }

    public function isClean(): bool
    {
        return $this->status === self::CLEAN;
    }

    public function isInfected(): bool
    {
        return $this->status === self::INFECTED;
    }

    /**
     * The scan did not produce a verdict. The content is unknown, not bad.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
