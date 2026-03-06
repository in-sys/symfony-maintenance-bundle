<?php

namespace INSYS\Bundle\MaintenanceBundle\Tests\Maintenance;

use INSYS\Bundle\MaintenanceBundle\Drivers\FileDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test driver file
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class FileMaintenanceTest extends TestCase
{
    static protected $tmpDir;
    protected $container;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$tmpDir = sys_get_temp_dir().'/symfony2_finder';
    }

    public function setUp(): void
    {
        $this->container = $this->initContainer();
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testDecide()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());

        $this->assertTrue($fileM->decide());

        $options = array('file_path' => self::$tmpDir.'/clok');

        $fileM2 = new FileDriver($options);
        $fileM2->setTranslator($this->getTranslator());
        $this->assertFalse($fileM2->decide());
    }

    public function testExceptionInvalidPath()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileDriver(array());
    }

    public function testLock()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertFileExists($options['file_path']);
    }

    public function testUnlock()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $fileM->unlock();

        $this->assertFileDoesNotExist($options['file_path']);
    }

    public function testIsExists()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertTrue($fileM->isEndTime(3600));
    }

    public function testMessages()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        // lock
        $this->assertEquals($fileM->getMessageLock(true), 'insys_maintenance.success_lock_file');
        $this->assertEquals($fileM->getMessageLock(false), 'insys_maintenance.not_success_lock');

        // unlock
        $this->assertEquals($fileM->getMessageUnlock(true), 'insys_maintenance.success_unlock');
        $this->assertEquals($fileM->getMessageUnlock(false), 'insys_maintenance.not_success_unlock');
    }

    protected function initContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'          => false,
            'kernel.bundles'        => array('MaintenanceBundle' => 'INSYS\Bundle\MaintenanceBundle'),
            'kernel.cache_dir'      => sys_get_temp_dir(),
            'kernel.environment'    => 'dev',
            'kernel.root_dir'       => __DIR__.'/../../../../', // src dir
            'kernel.default_locale' => 'fr',
        )));

        return $container;
    }

    public function getTranslator()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return $translator;
    }
}
