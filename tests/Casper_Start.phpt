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

class Casper_Start extends Tester\TestCase
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


  public function testStartWithoutUrl()
  {
    $this->casper->start()
        ->generate();

    $expected = <<<FRAGMENT
  var casper = require('casper').create({
    verbose: true,
    pageSettings: {
      javascriptEnabled: true,
      userAgent: 'casper'
    },
    viewportSize: {
      width: 1280,
      height: 720
    }
  });

  casper.start();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }

  public function testStartWithUrl()
  {
    $this->casper->start('https://www.google.com')
        ->generate();

    $expected = <<<FRAGMENT
  var casper = require('casper').create({
    verbose: true,
    pageSettings: {
      javascriptEnabled: true,
      userAgent: 'casper'
    },
    viewportSize: {
      width: 1280,
      height: 720
    }
  });

  casper.start();
  casper.then(function() {
    this.open('https://www.google.com', {
      headers: {
        'Accept': 'text/html'
      }
    });
  });

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }

}

(new Casper_Start())->run();
