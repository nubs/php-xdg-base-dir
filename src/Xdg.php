<?php

namespace XdgBaseDir;

use Icecave\Isolator\Isolator;

/**
 * Simple implementation of the XDG standard http://standards.freedesktop.org/basedir-spec/basedir-spec-latest.html
 *
 * Based on the python implementation https://github.com/takluyver/pyxdg/blob/master/xdg/BaseDirectory.py
 *
 * Class Xdg
 * @package ShopwareCli\Application
 */
class Xdg
{
    CONST S_IFDIR = 040000; // directory
    CONST S_IRWXO = 00007;  // rwx other
    CONST S_IRWXG  = 00056; // rwx group
    CONST RUNTIME_DIR_FALLBACK = '/tmp/php-xdg-runtime-dir-fallback-';

    private $isolator;
    
    /**
     * @return string
     */
    public function getHomeDir()
    {
        return $this->getenv('HOME');
    }

    /**
     * @return string
     */
    public function getHomeConfigDir()
    {
        $path = $this->getenv('XDG_CONFIG_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.config';

        return $path;
    }

    /**
     * @return string
     */
    public function getHomeDataDir()
    {
        $path = $this->getenv('XDG_DATA_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share';

        return $path;
    }

    /**
     * @return array
     */
    public function getConfigDirs()
    {
        $configDirsEnv = $this->getenv('XDG_CONFIG_DIRS');
        $configDirs = $configDirsEnv ? explode(':', $configDirsEnv) : array('/etc/xdg');

        $paths = array_merge(array($this->getHomeConfigDir()), $configDirs);

        return $paths;
    }

    /**
     * @return array
     */
    public function getDataDirs()
    {
        $dataDirsEnv = $this->getenv('XDG_DATA_DIRS');
        $dataDirs = $dataDirsEnv ? explode(':', $dataDirsEnv) : array('/usr/local/share', '/usr/share');

        $paths = array_merge(array($this->getHomeDataDir()), $dataDirs);
        return $paths;
    }

    /**
     * @return string
     */
    public function getHomeCacheDir()
    {
        $path = $this->getenv('XDG_CACHE_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.cache';

        return $path;

    }

    public function getRuntimeDir($strict=true)
    {
        if ($runtimeDir = $this->getenv('XDG_RUNTIME_DIR')) {
            return $runtimeDir;
        }

        if ($strict) {
            throw new \RuntimeException('XDG_RUNTIME_DIR was not set');
        }

        $fallback = self::RUNTIME_DIR_FALLBACK . $this->getenv('USER');

        $create = false;

        if (!$this->callGlobal('is_dir', array($fallback))) {
            $this->callGlobal('mkdir', array($fallback, 0700, true));
        }

        $st = $this->callGlobal('lstat', array($fallback));

        # The fallback must be a directory
        if (!$st['mode'] & self::S_IFDIR) {
            $this->callGlobal('rmdir', array($fallback));
            $create = true;
        } elseif ($st['uid'] != $this->callGlobal('getmyuid') ||
            $st['mode'] & (self::S_IRWXG | self::S_IRWXO)
        ) {
            $this->callGlobal('rmdir', array($fallback));
            $create = true;
        }

        if ($create) {
            $this->callGlobal('mkdir', array($fallback, 0700, true));
        }

        return $fallback;
    }

    public function setIsolator(Isolator $isolator = null)
    {
        $this->isolator = $isolator;
    }

    /**
     * @return string|false
     */
    private function getenv($name)
    {
        return $this->callGlobal('getenv', array($name));
    }

    /**
     * @return mixed
     */
    private function callGlobal($function, array $args = [])
    {
        if ($this->isolator !== null) {
            return call_user_func_array(array($this->isolator, $function), $args);
        }

        return call_user_func_array($function, $args);
    }

}
