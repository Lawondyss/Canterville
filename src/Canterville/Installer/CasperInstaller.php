<?php
/**
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installer;

use Canterville\Utils\Cli;
use Nette\Utils\FileSystem;

class CasperInstaller extends BaseInstaller
{
  protected function init()
  {
    $this->name = 'CasperJS';
    $this->version = '1.1-beta3';

    parent::init();
  }


  /**
   * @inheritdoc
   */
  protected function copyToBinFolder($binDir)
  {
    FileSystem::createDir($binDir);

    $source = $this->targetDir . '/bin/casperjs';
    $target = $binDir . '/casperjs';

    Cli::makeSymbolicLink($source, $target);
  }


  /**
   * @inheritdoc
   */
  protected function getUrl($version)
  {
    return 'https://github.com/n1k0/casperjs/zipball/' . $version;
  }


  /**
   * @param string $url
   * @return string
   */
  protected function getDistType($url)
  {
    return 'zip';
  }
}
