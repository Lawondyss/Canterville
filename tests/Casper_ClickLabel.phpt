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

class Casper_ClickLabel extends Tester\TestCase
{
  use FileContent;


  /** @var \Canterville\Casper */
  private $casper;

  /** @var string */
  private $label = 'some text of label';


  protected function setUp()
  {
    $this->casper = new Casper;
    $this->casper->setTempDir(TEMP_DIR);
  }


  protected function teardown()
  {
    Nette\Utils\FileSystem::delete(TEMP_DIR);
  }


  public function testLabel()
  {
    $this->casper->clickLabel($this->label)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.clickLabel('{$this->label}', undefined);
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testTag()
  {
    $tag = 'div';
    $this->casper->clickLabel($this->label, $tag)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.clickLabel('{$this->label}', '$tag');
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_ClickLabel())->run();
