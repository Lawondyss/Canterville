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

class Casper_MouseEvent extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $selector = 'div#id';


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
  public function testException($event)
  {
    Assert::exception(function() use ($event){
      $this->casper->mouseEvent($this->selector, $event);
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


  /**
   * @dataProvider dataCall
   */
  public function testCall($event)
  {
    $this->casper->mouseEvent($this->selector, $event)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.mouseEvent('$event', '{$this->selector}');
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function dataCall()
  {
    return [
      [Casper::EVENT_MOUSE_DOWN],
      [Casper::EVENT_MOUSE_MOVE],
      [Casper::EVENT_MOUSE_OUT],
      [Casper::EVENT_MOUSE_OVER],
      [Casper::EVENT_MOUSE_UP],
    ];
  }
}

(new Casper_MouseEvent())->run();
