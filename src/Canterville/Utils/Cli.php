<?php
/**
 * @package Canterville\Utils
 * @author Ladislav Vondráček <lad.von@gmail.com>
 */

namespace Canterville\Utils;

class Cli
{
  /**/
  public static function run($command)
  {
    $command .= ' > /dev/null 2>&1';
    $output = exec($command);

    return $output;
  }


  /**/
  public static function makeSymbolicLink($source, $target, $chmod = 0755)
  {
    $command = sprintf('ln -sf %s %s', $source, $target);
    $output = self::run($command);

    if (isset($chmod)) {
      chmod($target, $chmod);
    }

    return $output;
  }

}
