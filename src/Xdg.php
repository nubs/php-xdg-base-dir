<?php

namespace XdgBaseDir;

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

    /**
     * @return string
     */
    public function getHomeDir()
    {
        return getenv('HOME');
    }

    /**
     * @return string
     */
    public function getHomeConfigDir()
    {
        $path = getenv('XDG_CONFIG_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.config';

        return $path;
    }

    /**
     * @return string
     */
    public function getHomeDataDir()
    {
        $path = getenv('XDG_DATA_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share';

        return $path;
    }

    /**
     * @return array
     */
    public function getConfigDirs()
    {
        $configDirs = getenv('XDG_CONFIG_DIRS') ? explode(':', getenv('XDG_CONFIG_DIRS')) : array('/etc/xdg');

        $paths = array_merge(array($this->getHomeConfigDir()), $configDirs);

        return $paths;
    }

    /**
     * @return array
     */
    public function getDataDirs()
    {
        $dataDirs = getenv('XDG_DATA_DIRS') ? explode(':', getenv('XDG_DATA_DIRS')) : array('/usr/local/share', '/usr/share');

        $paths = array_merge(array($this->getHomeDataDir()), $dataDirs);
        return $paths;
    }

    /**
     * @return string
     */
    public function getHomeCacheDir()
    {
        $path = getenv('XDG_CACHE_HOME') ? : $this->getHomeDir() . DIRECTORY_SEPARATOR . '.cache';

        return $path;

    }

    public function getRuntimeDir($strict=true)
    {
        if ($runtimeDir = getenv('XDG_RUNTIME_DIR')) {
            return $runtimeDir;
        }

        if ($strict) {
            throw new \RuntimeException('XDG_RUNTIME_DIR was not set');
        }

        $fallback = self::RUNTIME_DIR_FALLBACK . getenv('USER');

        $create = false;

        if (!is_dir($fallback)) {
            mkdir($fallback, 0700, true);
        }

        $st = lstat($fallback);

        # The fallback must be a directory
        if (!$st['mode'] & self::S_IFDIR) {
            rmdir($fallback);
            $create = true;
        } elseif ($st['uid'] != getmyuid() ||
            $st['mode'] & (self::S_IRWXG | self::S_IRWXO)
        ) {
            rmdir($fallback);
            $create = true;
        }

        if ($create) {
            mkdir($fallback, 0700, true);
        }

        return $fallback;
    }

    /**
     * @return string|null
     */
    public function getConfigFile($relativePath)
    {
        foreach ($this->getConfigDirs() as $configDir) {
            $path = $configDir . DIRECTORY_SEPARATOR . $relativePath;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getDataFile($relativePath)
    {
        foreach ($this->getDataDirs() as $dataDir) {
            $path = $dataDir . DIRECTORY_SEPARATOR . $relativePath;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getCacheFile($relativePath)
    {
        $path = $this->getHomeCacheDir() . DIRECTORY_SEPARATOR . $relativePath;
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getRuntimeFile($relativePath)
    {
        $path = $this->getRuntimeDir() . DIRECTORY_SEPARATOR . $relativePath;
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

}
