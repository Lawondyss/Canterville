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

class Casper_Evaluate extends Tester\TestCase
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


  public function testCode()
  {
    $code = 'document.write("Lorem ipsum dolor sit amet.");';

    $this->casper->evaluate($code)
      ->generate();

    $expected = <<<FRAGMENT
  casper.evaluate(function() {
    $code
  });

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testArgsOne()
  {
    $code = 'document.write(message);';

    $this->casper->evaluate($code, ['message' => 'Lorem ipsum dolor sit amet.'])
      ->generate();

    $expected = <<<FRAGMENT
  casper.evaluate(function(message) {
    $code
  }, 'Lorem ipsum dolor sit amet.');

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testArgsTwo()
  {
    $code = 'document.write(msg1 + " " + msg2);';

    $this->casper->evaluate($code, ['msg1' => 'Lorem ipsum dolor', 'msg2' => 'sit amet.'])
      ->generate();

    $expected = <<<FRAGMENT
  casper.evaluate(function(msg1, msg2) {
    $code
  }, 'Lorem ipsum dolor', 'sit amet.');

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_Evaluate())->run();
