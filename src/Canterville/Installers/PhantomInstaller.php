<?php
/**
 * Class PhantomInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Canterville\RuntimeException;
use Canterville\Utils\Helpers;
use Nette\Utils\FileSystem;

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
    FileSystem::createDir($binDir);

    $os = Helpers::getOS();
    
    if ($os === Helpers::OS_WINDOWS) {
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
  protected function getUrl($version)
  {
    $url = 'https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-' . $version;
    $os = Helpers::getOS();

    switch ($os) {
      case Helpers::OS_WINDOWS:
        $url .= '-windows.zip';
        break;

      case Helpers::OS_LINUX:
        $bitSize = Helpers::getBitSize();
        switch ($bitSize) {
          case Helpers::BIT_32:
            $url .= '-linux-i686.tar.bz2';
            break;
          case Helpers::BIT_64:
            $url .= '-linux-x86_64.tar.bz2';
            break;
          default:
            // bit size unknown
            $url = false;
        }
        break;

      case Helpers::OS_MAC:
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

}
