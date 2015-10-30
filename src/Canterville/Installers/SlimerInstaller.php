<?php
/**
 * Class PhantomInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Canterville\Exception\RuntimeException;
use Canterville\Utils\Cli;
use Canterville\Utils\Helpers;
use Nette\Utils\FileSystem;

class SlimerInstaller extends BaseInstaller
{

  protected function init()
  {
    $this->name = 'SlimerJS';
    $this->version = '0.9.6';

    parent::init();
  }


  /**
   * Copies the SlimerJS binary to the bin folder
   *
   * @param string $binDir
   * @throws \Canterville\RuntimeException
   */
  protected function copyToBinFolder($binDir)
  {
    FileSystem::createDir($binDir);

    $os = Helpers::getOS();
    
    if ($os === Helpers::OS_WINDOWS) {
      $source = $this->targetDir . '/slimerjs.bat';
      $target = $binDir . '/slimerjs.bat';
    }
    elseif (isset($os)) {
      $source = $this->targetDir . '/slimerjs';
      $target = $binDir . '/slimerjs';
    }
    else {
      throw new RuntimeException('Cannot copy binary file of SlimerJS. OS not detect.');
    }

    Cli::makeSymbolicLink($source, $target);
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
    $url = 'http://download.slimerjs.org/releases/0.9.6/slimerjs-' . $version;
    $os = Helpers::getOS();

    switch ($os) {
      case Helpers::OS_WINDOWS:
        $url .= '-win32.zip';
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
        $url .= '-mac.tar.bz2';
        break;

      default:
        // OS unknown
        $url = false;
    }
    
    if ($url === false) {
      $msg = 'The Installer could not select a SlimerJS package for this OS.' .
        'Please install SlimerJS manually into the "/vendor/bin" folder of your project.';
      throw new RuntimeException($msg);
    }

    return $url;
  }

}
