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
use Nette\Utils\Strings;

abstract class BaseInstaller
{
  const AUTHOR_DIR = 'lawondyss/';


  protected $name;

  protected $version;

  protected $url;

  protected $distType;

  protected $targetDir;

  private $vendorDir;


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
    $this->url = $this->getUrl($this->version);
    $this->distType = $this->getDistType($this->url);
    $this->targetDir = $this->getVendorDir() . self::AUTHOR_DIR . Strings::lower($this->name) . '/';

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


  /**
   * Returns URL for download library
   *
   * @param string $version
   * @return string
   */
  abstract protected function getUrl($version);


  /**
   * Copy binary of library to directory of binaries
   *
   * @param string $binDir
   */
  abstract protected function copyToBinFolder($binDir);


  /**
   * @param string $url
   * @return string
   */
  protected function getDistType($url)
  {
    $distType = pathinfo($url, PATHINFO_EXTENSION) === 'zip' ? 'zip' : 'tar';

    return $distType;
  }


  /**
   * @return string
   */
  protected function getVendorDir()
  {
    if (!isset($this->vendorDir)) {
      $vendorDir = __DIR__ . '/../../../vendor';
      $this->vendorDir = realpath($vendorDir) . '/';
    }

    return $this->vendorDir;
  }

}
