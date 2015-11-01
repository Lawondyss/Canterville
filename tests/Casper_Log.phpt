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

class Casper_Log extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $message = 'Lorem ipsum dolor sit amet.';


  protected function setUp()
  {
    $this->casper = new Casper;
    $this->casper->setTempDir(TEMP_DIR);
  }


  protected function teardown()
  {
    Nette\Utils\FileSystem::delete(TEMP_DIR);
  }


  /**
   * @dataProvider dataException
   */
  public function testException($logLevel)
  {
    Assert::exception(function () use ($logLevel) {
      $this->casper->log($this->message, $logLevel);
    }, \Canterville\Exception\InvalidArgumentException::class);
  }


  public function dataException()
  {
    return [
      [null],
      [true],
      [false],
      [-1],
      [0],
      [1],
      [-1.0],
      [0.0],
      [1.0],
      ['-1'],
      ['0'],
      ['1'],
      ['-1.0'],
      ['0.0'],
      ['1.0'],
      ['foo'],
    ];
  }


  public function testMessage()
  {

    $this->casper->log($this->message)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.log('{$this->message}', 'info');
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  /**
   * @dataProvider dataLogLevel
   */
  public function testLogLevel($logLevel)
  {
    $this->casper->log($this->message, $logLevel)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.log('{$this->message}', '$logLevel');
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function dataLogLevel()
  {
    return [
      [Casper::LOG_LEVEL_DEBUG],
      [Casper::LOG_LEVEL_INFO],
      [Casper::LOG_LEVEL_WARNING],
      [Casper::LOG_LEVEL_ERROR],
    ];
  }
}

(new Casper_Log())->run();
