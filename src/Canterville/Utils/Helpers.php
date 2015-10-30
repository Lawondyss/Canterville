<?php
/**
 * @package Canterville\Utils
 * @author Ladislav Vondráček <lad.von@gmail.com>
 */

namespace Canterville\Utils;

use Nette\Utils\Json;

class Helpers
{
  const OS_MAC = 'macosx';
  const OS_WINDOWS = 'windows';
  const OS_LINUX = 'linux';

  const BIT_32 = 32;
  const BIT_64 = 64;


  /**
   * Returns name of operating system
   *
   * @return null|string
   */
  public static function getOS()
  {
    $os = null;
    $uName = strtolower(php_uname());

    if (strpos($uName, 'darwin') !== false) {
      $os = self::OS_MAC;
    }
    elseif (strpos($uName, 'win') !== false) {
      $os = self::OS_WINDOWS;
    }
    elseif (strpos($uName, 'linux') !== false) {
      $os = self::OS_LINUX;
    }

    return $os;
  }


  /**
   * Returns bites of operating system
   *
   * @return int|null
   */
  public static function getBitSize()
  {
    switch (PHP_INT_SIZE) {
      case 4:
        $bitSize = self::BIT_32;
        break;
      case 8:
        $bitSize = self::BIT_64;
        break;
      default:
        $bitSize = null;
    }

    return $bitSize;
  }


  /**
   * Returns argument prepared for used in JS script
   *
   * @param mixed $argument
   * @return mixed
   */
  public static function prepareArgument($argument)
  {
    switch (gettype($argument)) {
      case 'array':
      case 'object':
        $argument = Json::encode($argument, Json::PRETTY);
        break;
      case 'string':
        $argument = "'$argument'";
        break;
      case 'boolean':
        $argument = $argument ? 'true' : 'false';
        break;
      case 'NULL':
        $argument = 'undefined';
        break;
    }

    return $argument;
  }
}
