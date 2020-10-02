<?php
/**
 * Utopia PHP Framework
 *
 * @package ClamAV
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Appwrite\ClamAV\Tests;

use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use PHPUnit\Framework\TestCase;

class ClamAVTest extends TestCase
{
    /**
     * @var Network
     */
    protected $network;

    /**
     * @var Pipe
     */
    protected $pipe;

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

    public function testFileScan(): void
    {
        self::assertTrue($this->network->fileScan('/home/NoVirus.txt'));
        self::assertFalse($this->network->fileScan('/home/Virus.txt'));
    }

    public function testReload(): void
    {
        self::assertStringStartsWith('RELOADING', $this->network->reload());
    }
}
