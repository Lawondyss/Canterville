<?php
/**
 * CasperJS wrapper
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

use Nette\Utils\Json;

class Casper
{
  // array of functions that run if is debug, one argument is message
  public $onDebug = array();

  public $currentUrl;

  public $currentTitle;


  private $tagCurrentUrl = '[CURRENT_URL]';

  private $tagCurrentTitle = '[CURRENT_TITLE]';

  private $debug = false;

  private $userAgent = 'casper';

  private $output = array();

  private $requestedUrls = array();

  private $binDir;

  private $script = '';


  /************************** GETTERS AND SETTERS **************************/

  /**
   * @param boolean $debug
   * @return \Canterville\Casper
   */
  public function setDebug($debug = true)
  {
    $this->debug = (bool)$debug;
    return $this;
  }


  /**
   * @return bool
   */
  public function isDebug()
  {
    return $this->debug;
  }


  /**
   * @param string $userAgent
   * @return \Canterville\Casper
   */
  public function setUserAgent($userAgent)
  {
    $this->userAgent = $userAgent;
    return $this;
  }


  /**
   * @return string
   */
  public function getUserAgent()
  {
    return $this->userAgent;
  }


  /**
   * @return array
   */
  public function getOutput()
  {
    return $this->output;
  }


  /**
   * @return array
   */
  public function getRequestedUrls()
  {
    return $this->requestedUrls;
  }


  /**
   * @param string $binDir
   * @return \Canterville\Casper
   * @throws \Canterville\InvalidArgumentException
   */
  public function setBinDir($binDir)
  {
    $binDir = realpath($binDir);
    if ($binDir === false) {
      throw new InvalidArgumentException('The binary directory does not exist.');
    }
    $this->binDir = $binDir;
    return $this;
  }


  /**
   * @return null|string
   */
  public function getBinDir()
  {
    if (!isset($this->binDir)) {
      // expected location directory "vendor/lawondyss/canterville/src/Canterville"
      $this->binDir = realpath(__DIR__ . '/../../../../bin');
    }

    return $this->binDir;
  }


  /************************** HELPERS **************************/

  /**
   * Clear the current CasperJS script
   */
  private function clean()
  {
    $this->output = array();
    $this->requestedUrls = array();
    $this->currentUrl = null;
    $this->script = '';
  }


  /**
   * Processing output and debug
   */
  private function processOutput()
  {
    foreach ($this->output as $outputLine) {
      if (strpos($outputLine, $this->tagCurrentUrl) !== false) {
        $this->currentUrl = str_replace($this->tagCurrentUrl, '', $outputLine);
        continue;
      }
      if (strpos($outputLine, $this->tagCurrentTitle) !== false) {
        $this->currentTitle = str_replace($this->tagCurrentTitle, '', $outputLine);
        continue;
      }

      if (strpos($outputLine, 'Navigation requested: url=') !== false) {
        $frag0 = explode('Navigation requested: url=', $outputLine);
        $frag1 = explode(', type=', $frag0[1]);
        $this->requestedUrls[] = $frag1[0];
      }

      if ($this->isDebug()) {
        foreach ($this->onDebug as $debugFunction) {
          call_user_func($debugFunction, $outputLine);
        }
      }
    }
  }


  /************************** CASPER METHODS **************************/

  /**
   * Moves back a step in browser’s history
   *
   * @return \Canterville\Casper
   */
  public function back()
  {
    $fragment =
<<<FRAGMENT
  casper.back();

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Bypasses a given number of defined navigation steps
   *
   * @param int $count
   * @return \Canterville\Casper
   */
  public function bypass($count)
  {
    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.bypass($count);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Performs a click on the element matching the provided selector expression
   *
   * The method tries two strategies sequentially:
   * 1. trying to trigger a MouseEvent in Javascript
   * 2. using native QtWebKit event if the previous attempt failed
   *
   * @param string $selector
   * @return \Canterville\Casper
   */
  public function click($selector)
  {
    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.click('$selector');
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Clicks on the first DOM element found containing label text
   *
   * @param string $label
   * @param null|string $tag Element node name
   * @return \Canterville\Casper
   */
  public function clickLabel($label, $tag = null)
  {
    $tagFragment = isset($tag) ? "'$tag'" : 'undefined';

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.clickLabel('$label', $tagFragment);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Captures the entire page or defined area
   *
   * @param string $filename
   * @param null|array $area Area defined on top, left, width and height
   * @param null|array $options Defined options for format and quality
   * @return \Canterville\Casper
   * @throws \Canterville\InvalidArgumentException
   */
  public function capture($filename, array $area = null, array $options = null)
  {
    $areaFragment = 'undefined';
    $optionsFragment = 'undefined';

    if (isset($area)) {
      $msgError = 'Array in parameter $clipRect must contain key "%s".';
      if (!array_key_exists('top', $area)) {
        throw new InvalidArgumentException(sprintf($msgError, 'top'));
      }
      if (!array_key_exists('left', $area)) {
        throw new InvalidArgumentException(sprintf($msgError, 'left'));
      }
      if (!array_key_exists('width', $area)) {
        throw new InvalidArgumentException(sprintf($msgError, 'width'));
      }
      if (!array_key_exists('height', $area)) {
        throw new InvalidArgumentException(sprintf($msgError, 'height'));
      }

      $areaFragment = Json::encode($area);
    }

    if (isset($options)) {
      $optionsFragment = Json::encode($options);
    }

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.capture('$filename', $areaFragment, $optionsFragment);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Fills the fields of a form with given values and optionally submits it
   * Fields are referenced by their name attribute
   *
   * @param string $selector
   * @param array $values
   * @param boolean $submit
   * @return \Canterville\Casper
   */
  public function fill($selector, array $values, $submit = false)
  {
    $valuesFragment = Json::encode($values);
    $submitFragment = $submit ? 'true' : 'false';

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.fill('$selector', $valuesFragment, $submitFragment);
  });

FRAGMENT;

    $this->script .=  $fragment;

    return $this;
  }


  /**
   * Fills the fields of a form with given values and optionally submits it
   * Fields are referenced by CSS3 selectors
   *
   * @param string $selector
   * @param array $values
   * @param boolean $submit
   * @return \Canterville\Casper
   */
  public function fillSelectors($selector, array $values, $submit = false)
  {
    $valuesFragment = Json::encode($values);
    $submitFragment = $submit ? 'true' : 'false';

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.fillSelectors('$selector', $valuesFragment, $submitFragment);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Fills the fields of a form with given values and optionally submits it
   * Fields are referenced by XPath selectors
   *
   * @param string $selector
   * @param array $values
   * @param boolean $submit
   * @return \Canterville\Casper
   */
  public function fillXPath($selector, array $values, $submit = false)
  {
    $valuesFragment = Json::encode($values);
    $submitFragment = $submit ? 'true' : 'false';

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.fillXPath('$selector', $valuesFragment, $submitFragment);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Configures and starts Casper, then open the provided url
   *
   * @param string $url
   * @return \Canterville\Casper
   */
  public function start($url)
  {
    $this->clean();

    $fragment =
<<<FRAGMENT
  var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    colorizerType: 'Dummy'
  });
  casper.userAgent('$this->userAgent');
  casper.start().then(function() {
    this.open('$url', {
      headers: {
        'Accept': 'text/html'
      }
    });
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Runs the whole suite of steps
   *
   * @param boolean $removeScript
   */
  public function run($removeScript = true)
  {
    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.echo('{$this->tagCurrentUrl}' + this.getCurrentUrl());
    this.echo('{$this->tagCurrentTitle}' + this.getTitle());
  });

  casper.run();
FRAGMENT;

    $this->script .= $fragment;

    $filename = uniqid('casper-') . '.js';
    file_put_contents($filename, $this->script);

    $commands = array(
      'export PATH=' . $this->getBinDir() . ':$PATH',
      'casperjs ' . $filename,
    );

    exec(implode('; ', $commands), $this->output);
    $this->processOutput();

    if ($removeScript) {
      unlink($filename);
    }
  }

}
