<?php
/**
 * Class Installer
 *
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

use Canterville\Installer as CI;
use Composer\Script\Event;

class Installer
{
  /**
   * Instalation CasperJS
   */
  public static function casper(Event $event)
  {
    $composer = $event->getComposer();

    $casper = new CI\CasperInstaller;
    $casper->install($composer);

  }


  /**
   * Instalation PhantomJS
   */
  public static function phantom(Event $event)
  {
    $composer = $event->getComposer();

    $phantom = new CI\PhantomInstaller;
    $phantom->install($composer);
  }


  /**
   * Instalation SlimerJS
   */
  public static function slimer(Event $event)
  {
    $composer = $event->getComposer();

    $phantom = new CI\SlimerInstaller;
    $phantom->install($composer);
  }


  /**
   * Installation CasperJS on PhantomJS
   */
  public static function casperOnPhantom(Event $event)
  {
    self::phantom($event);
    self::casper($event);
  }


  /**
   * Installation CasperJS on SlimmerJS
   */
  public static function casperOnSlimmer(Event $event)
  {
    self::slimer($event);
    self::casper($event);
  }
}
