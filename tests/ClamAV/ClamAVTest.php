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

use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use PHPUnit\Framework\TestCase;

class ClamAVTest extends TestCase
{
    /**
     * @var Network|null
     */
    protected ?Network $network;

    /**
     * @var Pipe|null
     */
    protected ?Pipe $pipe;

    protected function setUp(): void
    {
        $this->network = new Network('localhost', 3310);
        $this->pipe = new Pipe();
    }

    protected function tearDown(): void
    {
        $this->network = null;
        $this->pipe = null;
    }

    public function testVersion(): void
    {
        self::assertStringStartsWith('ClamAV ', $this->network->version());
    }

    public function testPing(): void
    {
        self::assertTrue($this->network->ping());
    }

    public function testFileScanInStream(): void
    {
        self::assertTrue($this->network->fileScanInStream(__FILE__));
    }

    public function testReload(): void
    {
        self::assertStringStartsWith('RELOADING', $this->network->reload());
    }
}
