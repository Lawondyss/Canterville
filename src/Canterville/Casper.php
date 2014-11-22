<?php
/**
 * CasperJS wrapper
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

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
