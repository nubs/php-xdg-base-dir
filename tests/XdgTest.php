<?php

class XdgTest extends PHPUnit_Framework_TestCase
{
    private $isolator;

    public function setUp()
    {
        $this->isolator = $this->getMock('\Icecave\Isolator\Isolator', array('getenv', 'is_dir', 'mkdir', 'lstat', 'rmdir', 'getmyuid'));
    }

    /**
     * @return \XdgBaseDir\Xdg
     */
    public function getXdg()
    {
        $xdg = new \XdgBaseDir\Xdg();
        $xdg->setIsolator($this->isolator);

        return $xdg;
    }

    public function testXdgPutCache()
    {
        $this->expectSingleEnvironment('XDG_CACHE_HOME', 'tmp/');
        $this->assertEquals('tmp/', $this->getXdg()->getHomeCacheDir());
    }

    public function testXdgPutData()
    {
        $this->expectSingleEnvironment('XDG_DATA_HOME', 'tmp/');
        $this->assertEquals('tmp/', $this->getXdg()->getHomeDataDir());
    }

    public function testXdgPutConfig()
    {
        $this->expectSingleEnvironment('XDG_CONFIG_HOME', 'tmp/');
        $this->assertEquals('tmp/', $this->getXdg()->getHomeConfigDir());
    }

    public function testXdgDataDirsShouldIncludeHomeDataDir()
    {
        $this->isolator->expects($this->exactly(2))->method('getenv')->withConsecutive(array('XDG_DATA_DIRS'), array('XDG_DATA_HOME'))->will($this->onConsecutiveCalls('tmp/', 'home_dir/'));

        $this->assertSame(array('home_dir/', 'tmp/'), $this->getXdg()->getDataDirs());
    }

    public function testXdgConfigDirsShouldIncludeHomeConfigDir()
    {
        $this->isolator->expects($this->exactly(2))->method('getenv')->withConsecutive(array('XDG_CONFIG_DIRS'), array('XDG_CONFIG_HOME'))->will($this->onConsecutiveCalls('tmp/', 'home_dir/'));
        $this->assertSame(array('home_dir/', 'tmp/'), $this->getXdg()->getConfigDirs());
    }

    /**
     * If XDG_RUNTIME_DIR is set, it should be returned
     */
    public function testGetRuntimeDir()
    {
        $this->expectSingleEnvironment('XDG_RUNTIME_DIR', 'tmp/');
        $this->assertSame('tmp/', $this->getXdg()->getRuntimeDir());
    }

    /**
     * In strict mode, an exception should be shown if XDG_RUNTIME_DIR does not exist
     *
     * @expectedException \RuntimeException
     */
    public function testGetRuntimeDirShouldThrowException()
    {
        $this->expectSingleEnvironment('XDG_RUNTIME_DIR', false);
        $this->getXdg()->getRuntimeDir(true);
    }


    /**
     * In fallback mode a directory should be created
     */
    public function testGetRuntimeDirShouldCreateDirectory()
    {
        $fallbackDir = XdgBaseDir\Xdg::RUNTIME_DIR_FALLBACK . 'foo';
        $this->isolator->expects($this->exactly(2))->method('getenv')->withConsecutive(array('XDG_RUNTIME_DIR'), array('USER'))->will($this->onConsecutiveCalls(false, 'foo'));
        $this->isolator->expects($this->once())->method('is_dir')->with($fallbackDir)->willReturn(false);
        $this->isolator->expects($this->exactly(1))->method('mkdir')->with($fallbackDir, 0700, true)->willReturn(true);
        $this->isolator->expects($this->once())->method('lstat')->with($fallbackDir)->willReturn(array('mode' => 0700, 'uid' => 'my_uid'));
        $this->isolator->expects($this->once())->method('getmyuid')->willReturn('my_uid');
        $this->assertSame($fallbackDir, $this->getXdg()->getRuntimeDir(false));
    }


    /**
     * Ensure, that the fallback directories are created with correct permission
     */
    public function testGetRuntimeShouldDeleteDirsWithWrongPermission()
    {
        $fallbackDir = XdgBaseDir\Xdg::RUNTIME_DIR_FALLBACK . 'foo';
        $this->isolator->expects($this->exactly(2))->method('getenv')->withConsecutive(array('XDG_RUNTIME_DIR'), array('USER'))->will($this->onConsecutiveCalls(false, 'foo'));
        $this->isolator->expects($this->once())->method('is_dir')->with($fallbackDir)->willReturn(true);
        $this->isolator->expects($this->once())->method('lstat')->with($fallbackDir)->willReturn(array('mode' => 0764, 'uid' => 'my_uid'));
        $this->isolator->expects($this->once())->method('getmyuid')->willReturn('my_uid');
        $this->isolator->expects($this->exactly(1))->method('rmdir')->with($fallbackDir)->willReturn(true);
        $this->isolator->expects($this->exactly(1))->method('mkdir')->with($fallbackDir, 0700, true)->willReturn(true);
        $this->assertSame($fallbackDir, $this->getXdg()->getRuntimeDir(false));
    }

    private function expectSingleEnvironment($name, $value)
    {
        $this->isolator->expects($this->once())->method('getenv')->with($name)->willReturn($value);
    }
}
