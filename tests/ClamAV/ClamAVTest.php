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

namespace Utopia\Tests;

use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use PHPUnit\Framework\TestCase;

class ClamAVTest extends TestCase
{
    /**
     * @var Network
     */
    protected $network = null;

    /**
     * @var Pipe
     */
    protected $pipe = null;

    public function setUp()
    {
        $this->network = new Network('localhost', 3310);
        $this->pipe = new Pipe();
    }

    public function tearDown()
    {
        $this->network = null;
        $this->pipe= null;
    }

    public function testVersion()
    {
        $this->assertStringStartsWith('ClamAV ', $this->network->version());
    }

    public function testPing()
    {
        $this->assertEquals(true, $this->network->ping());
    }

    public function testFileScan()
    {
        $this->assertEquals(true, $this->network->fileScan('/home/NoVirus.txt'));
        $this->assertEquals(false, $this->network->fileScan('/home/Virus.txt'));
    }
}