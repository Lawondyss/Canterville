<?php
/**
 * Class PhantomInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Canterville\RuntimeException;

class SlimerInstaller extends BaseInstaller
{

  protected function init()
  {
    $this->name = 'SlimerJS';
    $this->version = '0.9.6';
    $this->url = $this->getUrl($this->version);
    $this->distType = $this->getDistType($this->url);
    $this->targetDir = 'vendor/lawondyss/slimerjs';

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
    if (!is_dir($binDir)) {
      mkdir($binDir);
    }
    
    $os = $this->getOS();
    
    if ($os === 'windows') {
      $source = '/slimerjs.bat';
      $target = $binDir . '/slimerjs.bat';
    }
    elseif (isset($os)) {
      $source = __DIR__ . '/../../../' . $this->targetDir . '/slimerjs';
      $target = $binDir . '/slimerjs';
    }
    else {
      throw new RuntimeException('Cannot copy binary file of SlimerJS. OS not detect.');
    }

    #copy($this->targetDir . $source, $target);
    #chmod($target, 0755);

    $command = 'ln -sf ' . $source . ' ' . $target;

    exec($command);
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
    $url = 'http://download.slimerjs.org/releases/0.9.6/slimerjs-' . $version;
    $os = $this->getOS();

    switch ($os) {
      case 'windows':
        $url .= '-win32.zip';
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
