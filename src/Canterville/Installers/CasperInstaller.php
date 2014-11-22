<?php
/**
 * Class CasperInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

class CasperInstaller extends BaseInstaller
{
  protected function init()
  {
    $this->name = 'CasperJS';
    $this->version = '1.1-beta3';
    $this->url = 'https://github.com/n1k0/casperjs/zipball/' . $this->version;
    $this->distType = 'zip';
    $this->targetDir = 'vendor/lawondyss/casperjs';
  }


  /**
   * Make link on the CasperJS to the bin folder.
   */
  protected function copyToBinFolder($binDir)
  {
    if (!is_dir($binDir)) {
      mkdir($binDir);
    }

    $source = __DIR__ . '/../../../' . $this->targetDir . '/bin/casperjs';
    $target = $binDir . '/casperjs';
    $command = 'ln -sf ' . $source . ' ' . $target;

    exec($command);
    chmod($target, 0755);
  }
}
