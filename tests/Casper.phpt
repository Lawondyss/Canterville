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

class CasperTest extends Tester\TestCase
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


  public function testLogLevel()
  {
    Assert::same(Casper::LOG_LEVEL_INFO, $this->casper->getLogLevel());

    Assert::same($this->casper, $this->casper->setLogLevel(Casper::LOG_LEVEL_ERROR));
    Assert::same(Casper::LOG_LEVEL_ERROR, $this->casper->getLogLevel());

    Assert::same($this->casper, $this->casper->setLogLevel(Casper::LOG_LEVEL_DEBUG));
    Assert::same(Casper::LOG_LEVEL_DEBUG, $this->casper->getLogLevel());

    Assert::same($this->casper, $this->casper->setLogLevel(Casper::LOG_LEVEL_WARNING));
    Assert::same(Casper::LOG_LEVEL_WARNING, $this->casper->getLogLevel());

    Assert::same($this->casper, $this->casper->setLogLevel(Casper::LOG_LEVEL_INFO));
    Assert::same(Casper::LOG_LEVEL_INFO, $this->casper->getLogLevel());
  }


  public function testEngine()
  {
    Assert::same(Casper::ENGINE_PHANTOMJS, $this->casper->getEngine());

    Assert::same($this->casper, $this->casper->setEngine(Casper::ENGINE_SLIMMERJS));
    Assert::same(Casper::ENGINE_SLIMMERJS, $this->casper->getEngine());

    Assert::same($this->casper, $this->casper->setEngine(Casper::ENGINE_PHANTOMJS));
    Assert::same(Casper::ENGINE_PHANTOMJS, $this->casper->getEngine());
  }


  public function testUserAgent()
  {
    Assert::same('casper', $this->casper->getUserAgent());

    Assert::same($this->casper, $this->casper->setUserAgent('test'));
    Assert::same('test', $this->casper->getUserAgent());
  }


  public function testGetOutput()
  {
    Assert::same([], $this->casper->getOutput());
  }


  public function testGetRequests()
  {
    Assert::same([], $this->casper->getRequests());
  }


  public function testBinDir()
  {
    Assert::exception(function() {
      $this->casper->setBinDir('path/not/exists/');
    }, \Canterville\Exception\InvalidArgumentException::class);

    Assert::same($this->casper, $this->casper->setBinDir(TEMP_DIR));
    Assert::same(TEMP_DIR, $this->casper->getBinDir());
  }


  public function testTempDir()
  {
    Assert::same(TEMP_DIR, $this->casper->getTempDir());

    $newTempDir = TEMP_DIR . '/' . md5(TEMP_DIR);
    Assert::false(is_dir($newTempDir));
    Assert::same($this->casper, $this->casper->setTempDir($newTempDir));
    Assert::true(is_dir($newTempDir));
    Assert::same($newTempDir, $this->casper->getTempDir());
  }


  public function testOptions()
  {
    $defaultOptions = [
        'log-level' => Casper::LOG_LEVEL_INFO,
        'engine' => Casper::ENGINE_PHANTOMJS
    ];
    Assert::same($defaultOptions, $this->casper->getOptions());

    $newOptions = [md5(TEMP_DIR) => TEMP_DIR];
    Assert::same($this->casper, $this->casper->setOptions($newOptions));
    Assert::same($newOptions, $this->casper->getOptions());
  }


  public function testOption()
  {
    $newOption = 'not-exists-option';

    Assert::exception(function() use ($newOption) {
      $this->casper->getOption($newOption);
    }, \Canterville\Exception\NotExistsException::class);

    Assert::same($this->casper, $this->casper->setOption($newOption));
    Assert::null($this->casper->getOption($newOption));

    $randomValue = md5(TEMP_DIR);
    Assert::same($this->casper, $this->casper->setOption($newOption, $randomValue));
    Assert::same($randomValue, $this->casper->getOption($newOption));
  }


  public function testRun()
  {
    $this->casper->run(true);

    $expected = <<<FRAGMENT
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testBack()
  {
    $this->casper->back()
        ->run(true);

    $expected = <<<FRAGMENT
  casper.back();
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  /**
   * @dataProvider dataBypass
   */
  public function testBypass($count)
  {
    $this->casper->bypass($count)
        ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.bypass($count);
  });
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function dataBypass()
  {
    return [
      [1],
      [2],
      [3],
      [4],
    ];
  }


  public function testScrollToBottom()
  {
    $this->casper->scrollToBottom()
        ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.scrollToBottom();
    this.echo("[scrollToBottom]");
  });
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  /**
   * @dataProvider dataWait
   */
  public function testWait($seconds)
  {
    $this->casper->wait($seconds)
        ->run(true);

    $mSeconds = $seconds * 1000;

    $expected = <<<FRAGMENT
  casper.wait($mSeconds, function() {
    this.echo('[wait] time $seconds sec occurred');
  });
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function dataWait()
  {
    return [
      [1],
      [2],
      [3],
      [4],
    ];
  }


  public function testWriteCurrentUrl()
  {
    $this->casper->writeCurrentUrl()
        ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo('[currentUrl] ' + this.getCurrentUrl());
  });
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }


  public function testWriteFetchText()
  {
    $this->casper->writeTitle()
        ->run(true);

    $expected = <<<FRAGMENT
  casper.then(function() {
    this.echo('[title] ' + this.getTitle());
  });
  casper.run();

FRAGMENT;

    Assert::same($expected, $this->getCasperContent());
  }
}

(new CasperTest())->run();
