<?php
/**
 * Class BaseInstaller
 * @package Canterville\Installers
 * @author Ladislav Vondráček
 */

namespace Canterville\Installers;

use Canterville\NonExistsException;
use Canterville\NotSetException;
use Composer\Composer;
use Composer\Package\Package;
use Composer\Package\Version\VersionParser;

abstract class BaseInstaller
{
  protected $name;

  protected $version;

  protected $url;

  protected $distType;

  protected $targetDir;


  /**
   * Installation package
   *
   * @param Composer $composer
   */
  public function install(Composer $composer)
  {
    $this->init();
    
    /* Create Composer in-memory package */
    $versionParser = new VersionParser;
    $normVersion = $versionParser->normalize($this->version);

    $package = new Package($this->name, $normVersion, $this->version);
    $package->setInstallationSource('dist');
    $package->setDistUrl($this->url);
    $package->setDistType($this->distType);
    $package->setTargetDir($this->targetDir);

    /* Download the Archive */
    $downloadManager = $composer->getDownloadManager();
    $downloadManager->download($package, $this->targetDir, false);

    /* Copy binary to the "bin" file */
    $binDir = $composer->getConfig()->get('bin-dir');
    $this->copyToBinFolder($binDir);
  }


  /**
   * Method run before install
   *
   * @throws \Canterville\NonExistsException
   * @throws \Canterville\NotSetException
   */
  protected function init()
  {
    $calledClass = get_called_class();

    if (!method_exists($calledClass, 'copyToBinFolder')) {
      throw new NonExistsException(sprintf('Method "' . $calledClass . '::%s" non exists.', 'copyToBinFolder'));
    }

    $errorMsg = 'Property "' . $calledClass . '::$%s" not set.';
    if (!isset($this->name)) {
      throw new NotSetException($errorMsg, 'name');
    }
    if (!isset($this->version)) {
      throw new NotSetException($errorMsg, 'version');
    }
    if (!isset($this->url)) {
      throw new NotSetException($errorMsg, 'url');
    }
    if (!isset($this->distType)) {
      throw new NotSetException($errorMsg, 'distType');
    }
    if (!isset($this->targetDir)) {
      throw new NotSetException($errorMsg, 'targetDir');
    }
  }

}
