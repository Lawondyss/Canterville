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

class Casper_getHTML extends Tester\TestCase
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


  public function testException()
  {
    Assert::exception(function() {
      $this->casper->getHTML(null, null, true);
    }, \Canterville\Exception\InvalidArgumentException::class);
  }


  public function testWithoutArguments()
  {
    $this->casper->getHTML()
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo(this.getHTML(undefined, false));
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testFilename()
  {
    $filename = TEMP_DIR . '/' . md5(TEMP_DIR);

    $this->casper->getHTML($filename)
      ->generate();

    $expected = <<<FRAGMENT
  var fs = require('fs');
  casper.then(function() {
    fs.write('$filename', this.getHTML(undefined, false));
    this.echo("[save] HTML to '$filename'");
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testSelector()
  {
    $selector = 'head';

    $this->casper->getHTML(null, $selector)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo(this.getHTML('$selector', false));
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testSelectorAndFilename()
  {
    $filename = TEMP_DIR . '/' . md5(TEMP_DIR);
    $selector = 'head';

    $this->casper->getHTML($filename, $selector)
      ->generate();

    $expected = <<<FRAGMENT
  var fs = require('fs');
  casper.then(function() {
    fs.write('$filename', this.getHTML('$selector', false));
    this.echo("[save] HTML to '$filename'");
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testOuter()
  {
    $selector = 'head';

    $this->casper->getHTML(null, $selector, true)
      ->generate();

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo(this.getHTML('$selector', true));
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testOuterAndFilename()
  {
    $filename = TEMP_DIR . '/' . md5(TEMP_DIR);
    $selector = 'head';

    $this->casper->getHTML($filename, $selector, true)
        ->generate();

    $expected = <<<FRAGMENT
  var fs = require('fs');
  casper.then(function() {
    fs.write('$filename', this.getHTML('$selector', true));
    this.echo("[save] HTML to '$filename'");
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }
}

(new Casper_getHTML())->run();
