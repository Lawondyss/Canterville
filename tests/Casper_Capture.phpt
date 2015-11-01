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

class Casper_Capture extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $filename = TEMP_DIR . '/capture.jpg';


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
    $this->casper->capture($this->filename)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.capture('{$this->filename}', undefined, undefined);
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testArea()
  {
    Assert::exception(function () {
      $this->casper->capture('', []);
    }, \Canterville\Exception\InvalidArgumentException::class);

    Assert::exception(function () {
      $this->casper->capture('', [Casper::CAPTURE_AREA_TOP => 0]);
    }, \Canterville\Exception\InvalidArgumentException::class);

    Assert::exception(function () {
      $this->casper->capture('', [Casper::CAPTURE_AREA_TOP => 0, Casper::CAPTURE_AREA_LEFT => 0]);
    }, \Canterville\Exception\InvalidArgumentException::class);

    Assert::exception(function () {
      $this->casper->capture('', [Casper::CAPTURE_AREA_TOP => 0, Casper::CAPTURE_AREA_LEFT => 0, Casper::CAPTURE_AREA_WIDTH => 0]);
    }, \Canterville\Exception\InvalidArgumentException::class);

    $this->casper->capture($this->filename, [Casper::CAPTURE_AREA_TOP => 0, Casper::CAPTURE_AREA_LEFT => 0, Casper::CAPTURE_AREA_WIDTH => 0, Casper::CAPTURE_AREA_HEIGHT => 0])
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.capture('{$this->filename}', {
    "top": 0,
    "left": 0,
    "width": 0,
    "height": 0
}, undefined);
  });

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testOptions()
  {
    $this->casper->capture($this->filename, null, ['format' => 'jpg', 'quality' => '100'])
        ->generate();
    $expected = <<<FRAGMENT
  casper.then(function() {
    this.capture('{$this->filename}', undefined, {
    "format": "jpg",
    "quality": "100"
});
  });

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_Capture())->run();
