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

class Casper_Fill extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $selector = 'form#form';


  protected function setUp()
  {
    $this->casper = new Casper;
    $this->casper->setTempDir(TEMP_DIR);
  }


  protected function teardown()
  {
    Nette\Utils\FileSystem::delete(TEMP_DIR);
  }


  public function testSelector()
  {
    $this->casper->fill($this->selector, [])
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fill('{$this->selector}', [], false);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testValues()
  {
    $values = ['nick' => 'Lawondyss', 'email' => 'lad.von@gmail.com'];
    $this->casper->fill($this->selector, $values)
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fill('{$this->selector}', {
    "nick": "Lawondyss",
    "email": "lad.von@gmail.com"
}, false);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testSubmit()
  {
    $this->casper->fill($this->selector, [], true)
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fill('{$this->selector}', [], true);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_Fill())->run();
