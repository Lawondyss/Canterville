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

class Casper_FillSelectors extends Tester\TestCase
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
    $this->casper->fillSelectors($this->selector, [])
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fillSelectors('{$this->selector}', [], false);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testValues()
  {
    $values = ['#input-nick' => 'Lawondyss', '#input-email' => 'lad.von@gmail.com'];
    $this->casper->fillSelectors($this->selector, $values)
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fillSelectors('{$this->selector}', {
    "#input-nick": "Lawondyss",
    "#input-email": "lad.von@gmail.com"
}, false);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }


  public function testSubmit()
  {
    $this->casper->fillSelectors($this->selector, [], true)
      ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.fillSelectors('{$this->selector}', [], true);
  });
  casper.run();

FRAGMENT;
    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_FillSelectors())->run();
