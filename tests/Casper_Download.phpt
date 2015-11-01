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

class Casper_Download extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $filename = TEMP_DIR . '/down.load';


  protected function setUp()
  {
    $this->casper = new Casper;
    $this->casper->setTempDir(TEMP_DIR);
  }


  protected function teardown()
  {
    Nette\Utils\FileSystem::delete(TEMP_DIR);
  }


  public function testFilename()
  {
    $this->casper->download($this->filename)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.download(this.getCurrentUrl(), '{$this->filename}');
  });

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testUrl()
  {
    $url = 'https://www.google.com/';
    $this->casper->download($this->filename, $url)
        ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.download('$url', '{$this->filename}');
  });

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_Download())->run();
