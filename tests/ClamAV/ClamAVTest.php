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
use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Socket;

class ClamAVTest extends TestCase
{
    protected ?Network $network;

    protected ?Pipe $pipe;

    protected function setUp(): void
    {
        $this->network = ClamAV::createFromDSN('tcp://127.0.0.1:3310');
        $this->pipe = ClamAV::createFromDSN('unix:///var/run/clamav/clamd.ctl');
    }

    protected function tearDown(): void
    {
        $this->network = null;
        $this->pipe = null;
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     *
     * @throws ReflectionException
     */
    public function testGetSocket(): void
    {
        $pipe = new Pipe();

        $reflection = new ReflectionClass($pipe);
        $method = $reflection->getMethod('getSocket');
        $method->setAccessible(true);

        self::assertInstanceOf(Socket::class, $method->invoke($pipe));
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     *
     * @throws ReflectionException
     */
    public function testGetSocketThrowsException(): void
    {
        // Provide an invalid path to cause socket creation to fail
        $network = new Network('1.2.3.4', 1234);

        $reflection = new ReflectionClass($network);
        $method = $reflection->getMethod('getSocket');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);

        $method->invoke($network);
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testVersion(): void
    {
        self::assertStringStartsWith('ClamAV ', $this->network->version());
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testPing(): void
    {
        self::assertIsBool($this->network->ping());
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testFileScan(): void
    {
        self::assertIsBool($this->network->fileScan(__FILE__));
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testContinueScan(): void
    {
        $results = $this->network->continueScan(__FILE__);

        self::assertIsArray($results);
        self::assertNotEmpty($results[0]['file']);
        self::assertNotEmpty($results[0]['stats']);
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testFileScanInStream(): void
    {
        self::assertIsBool($this->network->fileScanInStream(__FILE__));
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testReload(): void
    {
        self::assertStringStartsWith('RELOADING', $this->network->reload());
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testDSNCreation(): void
    {
        self::assertInstanceOf(Network::class, ClamAV::createFromDSN('tcp://localhost:3310'));
        self::assertInstanceOf(Pipe::class, ClamAV::createFromDSN('unix:///var/run/clamav/clamd.ctl'));
    }

    /**
     * @covers \Appwrite\ClamAV\ClamAV
     * @covers \Appwrite\ClamAV\Network
     * @covers \Appwrite\ClamAV\Pipe
     */
    public function testDSNCreationWithInvalidDSN(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ClamAV::createFromDSN('foo://bar');
    }
}
