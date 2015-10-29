<?php
/**
 * Class PhantomInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Canterville\RuntimeException;

class PhantomInstaller extends BaseInstaller
{

  protected function init()
  {
    $this->name = 'PhantomJS';
    $this->version = '1.9.8';
    $this->url = $this->getUrl($this->version);
    $this->distType = $this->getDistType($this->url);
    $this->targetDir = 'vendor/lawondyss/phantomjs';

    parent::init();
  }


  /**
   * Copies the PhantomJS binary to the bin folder
   *
   * @param string $binDir
   * @throws \Canterville\RuntimeException
   */
  protected function copyToBinFolder($binDir)
  {
    if (!is_dir($binDir)) {
      mkdir($binDir);
    }
    
    $os = $this->getOS();
    
    if ($os === 'windows') {
      $source = '/phantomjs.exe';
      $target = $binDir . '/phantomjs.exe';
    }
    elseif (isset($os)) {
      $source = '/bin/phantomjs';
      $target = $binDir . '/phantomjs';
    }
    else {
      throw new RuntimeException('Cannot copy binary file of PhantomJS. OS not detect.');
    }

    copy($this->targetDir . $source, $target);
    chmod($target, 0755);
  }


  /**
   * URL of the PhantomJS distribution for the installing
   *
   * @param string $version
   * @return string
   * @throws \Canterville\RuntimeException
   */
  private function getUrl($version)
  {
    $url = 'https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-' . $version;
    $os = $this->getOS();

    switch ($os) {
      case 'windows':
        $url .= '-windows.zip';
        break;

      case 'linux':
        $bitSize = $this->getBitSize();
        switch ($bitSize) {
          case 32:
            $url .= '-linux-i686.tar.bz2';
            break;
          case 64:
            $url .= '-linux-x86_64.tar.bz2';
            break;
          default:
            // bit size unknown
            $url = false;
        }
        break;

      case 'macosx':
        $url .= '-macosx.zip';
        break;

      default:
        // OS unknown
        $url = false;
    }
    
    if ($url === false) {
      $msg = 'The Installer could not select a PhantomJS package for this OS.' .
        'Please install PhantomJS manually into the "/vendor/bin" folder of your project.';
      throw new RuntimeException($msg);
    }

    return $url;
  }


  /**
   * @return null|string
   */
  private function getOS()
  {
    $os = null;
    $uname = strtolower(php_uname());

    if (strpos($uname, 'darwin') !== false) {
      $os = 'macosx';
    }
    elseif (strpos($uname, 'win') !== false) {
      $os = 'windows';
    }
    elseif (strpos($uname, 'linux') !== false) {
      $os = 'linux';
    }

    return $os;
  }


  /**
   * @return int|null
   */
  private function getBitSize()
  {
    switch (PHP_INT_SIZE) {
      case 4:
        $bitSize = 32;
        break;
      case 8:
        $bitSize = 64;
        break;
      default:
        $bitSize = null;
    }

    return $bitSize;
  }


  /**
   * @param string $url
   * @return string
   */
  private function getDistType($url)
  {
    $distType = pathinfo($url, PATHINFO_EXTENSION) === 'zip' ? 'zip' : 'tar';

    return $distType;
  }

}
