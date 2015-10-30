<?php
/**
 * Class Installer
 *
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

use Canterville\Installers as CI;
use Composer\Script\Event;

class Installer
{
  /**
   * Installation all required libraries
   */
  public static function install(Event $event)
  {
    $composer = $event->getComposer();

    $phantom = new CI\SlimerInstaller;
    $phantom->install($composer);

    $casper = new CI\CasperInstaller;
    $casper->install($composer);
  }
}
