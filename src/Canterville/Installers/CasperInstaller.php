<?php
/**
 * Class CasperInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Nette\Utils\FileSystem;

class CasperInstaller extends BaseInstaller
{
  protected function init()
  {
    $this->name = 'CasperJS';
    $this->version = '1.1-beta3';
    $this->url = $this->getUrl($this->version);
    $this->distType = 'zip';
    $this->targetDir = 'vendor/lawondyss/casperjs';
  }


  /**
   * @inheritdoc
   */
  protected function copyToBinFolder($binDir)
  {
    FileSystem::createDir($binDir);

    $source = __DIR__ . '/../../../' . $this->targetDir . '/bin/casperjs';
    $target = $binDir . '/casperjs';
    $command = 'ln -sf ' . $source . ' ' . $target;

    exec($command);
    chmod($target, 0755);
  }


  /**
   * @inheritdoc
   */
  protected function getUrl($version)
  {
    return 'https://github.com/n1k0/casperjs/zipball/' . $version;
  }
}
