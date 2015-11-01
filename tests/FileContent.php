<?php
/**
 * FileContent
 */

namespace Tests;

trait FileContent
{
  /**
   * Returns content of first JS file in TEMP_DIR
   *
   * @return string
   */
  public function getCasperContent()
  {
    $casperFile = glob(TEMP_DIR . '/*.js')[0];
    $content = file_get_contents($casperFile);

    return $content;
  }
}
