<?php
/**
 * @author Ladislav Vondráček <lad.von@gmail.com>
 * @package Tests
 */

namespace Tests;

require_once __DIR__ . '/bootstrap.php';

use Canterville\Casper;
use Nette;
use Tester;
use Tester\Assert;

class Casper_GetPageContent extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;


  protected function setUp()
  {
    $this->casper = new Casper;
    $this->casper->setTempDir(TEMP_DIR);
  }


  protected function teardown()
  {
    Nette\Utils\FileSystem::delete(TEMP_DIR);
  }


  public function testWithoutArgument()
  {
    $this->casper->getPageContent()
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo(this.getPageContent());
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testFilename()
  {
    $filename = TEMP_DIR . '/' . md5(TEMP_DIR);

    $this->casper->getPageContent($filename)
      ->generate();

    $expected = <<<FRAGMENT
  var fs = require('fs');
  casper.then(function() {
    fs.write('$filename', this.getPageContent());
    this.echo("[save] page content to '$filename'");
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }
}

(new Casper_GetPageContent())->run();
