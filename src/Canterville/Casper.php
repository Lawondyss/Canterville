<?php
/**
 * CasperJS wrapper
 *
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

use Canterville\Exception\InvalidArgumentException;
use Canterville\Exception\NotExistsException;
use Canterville\Utils\Helpers;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Nette\Utils\FileSystem;

class Casper
{
  const EVENT_MOUSE_UP = 'mouseup';
  const EVENT_MOUSE_DOWN = 'mousedown';
  const EVENT_CLICK = 'click';
  const EVENT_MOUSE_MOVE = 'mousemove';
  const EVENT_MOUSE_OVER = 'mouseover';
  const EVENT_MOUSE_OUT = 'mouseout';

  const SEND_KEYS_OPTION_RESET = 'reset';
  const SEND_KEYS_OPTION_KEEP_FOCUS = 'keepFocus';
  const SEND_KEYS_OPTION_MODIFIERS = 'modifiers';

  const MODIFIER_CTRL = 'ctrl';
  const MODIFIER_ALT = 'alt';
  const MODIFIER_SHIFT = 'shift';
  const MODIFIER_META = 'meta';
  const MODIFIER_KEYPAD = 'keypad';

  const OPEN_OPTION_METHOD = 'method';
  const OPEN_OPTION_DATA = 'data';
  const OPEN_OPTION_HEADERS = 'headers';

  const METHOD_GET = 'get';
  const METHOD_POST = 'post';
  const METHOD_PUT = 'put';
  const METHOD_DELETE = 'delete';
  const METHOD_HEAD = 'head';

  const CAPTURE_AREA_TOP = 'top';
  const CAPTURE_AREA_LEFT = 'left';
  const CAPTURE_AREA_WIDTH = 'width';
  const CAPTURE_AREA_HEIGHT = 'height';

  const CAPTURE_OPTION_FORMAT = 'format';
  const CAPTURE_OPTION_QUALITY = 'quality';

  const LOG_LEVEL_DEBUG = 'debug';
  const LOG_LEVEL_INFO = 'info';
  const LOG_LEVEL_WARNING = 'warning';
  const LOG_LEVEL_ERROR = 'error';

  const ENGINE_SLIMMERJS = 'slimerjs';
  const ENGINE_PHANTOMJS = 'phantomjs';


  // array of functions that run if is debug, one argument is message
  public $onLog = [];

  public $currentUrl;

  public $currentTitle;

  private $userAgent = 'casper';

  private $output = [];

  private $requests = [];

  private $binDir;

  private $script = '';

  private $options = [
      'log-level' => self::LOG_LEVEL_INFO,
      'engine' => self::ENGINE_PHANTOMJS,
  ];

  private $useFsModule = false;


  /************************** GETTERS AND SETTERS **************************/

  /**
   * @param string $logLevel
   * @return \Canterville\Casper
   */
  public function setLogLevel($logLevel)
  {
    $this->options['log-level'] = $logLevel;

    return $this;
  }


  /**
   * @return string
   * @throws \Canterville\Exception\NotExistsException
   */
  public function getLogLevel()
  {
    return $this->getOption('log-level');
  }


  /**
   * @param string $engine
   * @return \Canterville\Casper
   */
  public function setEngine($engine)
  {
    $this->setOption('engine', $engine);

    return $this;
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
   * @param null|string $name
   * @return array
   */
  public function getRequests($name = null)
  {
    if (!isset($name)) {
      return $this->requests;
    }

    $requests = [];
    foreach ($this->requests as $key => $request) {
      $requests[$key] = isset($request[$name]) ? $request[$name] : null;
    }

    return $requests;
  }


  /**
   * @param string $binDir
   * @return \Canterville\Casper
   * @throws \Canterville\Exception\InvalidArgumentException
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


  /**
   * @param array $options [name-of-option => value]
   * @return \Canterville\Casper
   */
  public function setOptions(array $options)
  {
    $this->options = $options;

    return $this;
  }


  /**
   * @param string $name
   * @param null|string $value
   * @return \Canterville\Casper
   */
  public function setOption($name, $value = null)
  {
    $this->options[$name] = $value;

    return $this;
  }


  /**
   * @param string $name
   * @return mixed
   * @throws \Canterville\Exception\NotExistsException
   */
  public function getOption($name)
  {
    if (!array_key_exists($name, $this->options)) {
      $msg = sprintf('Option "%s" not set.', $name);
      throw new NotExistsException($msg);
    }

    return $this->options[$name];
  }


  /**
   * @return array
   */
  public function getOptions()
  {
    return $this->options;
  }


  /************************** CASPER METHODS **************************/

  /**
   * Moves back a step in browser’s history
   *
   * @return \Canterville\Casper
   */
  public function back()
  {
    $fragment = <<<FRAGMENT
  casper.back();
FRAGMENT;

    $this->addFragment($fragment);

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
    $count = Helpers::prepareArgument($count);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.bypass($count);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Captures the entire page or defined area
   *
   * @param string $filename
   * @param null|array $area Area defined on top, left, width and height
   * @param null|array $options Defined options for format and quality
   * @return \Canterville\Casper
   * @throws \Canterville\Exception\InvalidArgumentException
   */
  public function capture($filename, array $area = null, array $options = null)
  {
    $filename = Helpers::prepareArgument($filename);

    if (isset($area)) {
      $validKeys = [
          self::CAPTURE_AREA_TOP,
          self::CAPTURE_AREA_LEFT,
          self::CAPTURE_AREA_WIDTH,
          self::CAPTURE_AREA_HEIGHT,
      ];
      $check = $this->checkValidKeys($validKeys, $area);

      if (count($check) > 0) {
        $msg = sprintf('In parameter $area is this invalid keys: %s', implode(', ', $check));
        throw new InvalidArgumentException($msg);
      }

      $msgError = 'Array in parameter $area must contain key "%s".';
      if (!array_key_exists(self::CAPTURE_AREA_TOP, $area)) {
        throw new InvalidArgumentException(sprintf($msgError, self::CAPTURE_AREA_TOP));
      }
      if (!array_key_exists(self::CAPTURE_AREA_LEFT, $area)) {
        throw new InvalidArgumentException(sprintf($msgError, self::CAPTURE_AREA_LEFT));
      }
      if (!array_key_exists(self::CAPTURE_AREA_WIDTH, $area)) {
        throw new InvalidArgumentException(sprintf($msgError, self::CAPTURE_AREA_WIDTH));
      }
      if (!array_key_exists(self::CAPTURE_AREA_HEIGHT, $area)) {
        throw new InvalidArgumentException(sprintf($msgError, self::CAPTURE_AREA_HEIGHT));
      }
    }

    $area = Helpers::prepareArgument($area);
    $options = Helpers::prepareArgument($options);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.capture($filename, $area, $options);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Performs a click on the element matching the provided selector expression
   * The method tries two strategies sequentially:
   * 1. trying to trigger a MouseEvent in Javascript
   * 2. using native QtWebKit event if the previous attempt failed
   *
   * @param string $selector
   * @return \Canterville\Casper
   */
  public function click($selector)
  {
    $selector = Helpers::prepareArgument($selector);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.click($selector);
  });
FRAGMENT;

    $this->addFragment($fragment);

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
    $label = Helpers::prepareArgument($label);
    $tag = Helpers::prepareArgument($tag);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.clickLabel($label, $tag);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Saves a remote resource onto the filesystem
   *
   * @param string $filename
   * @param null|string $url If null then download current page
   * @return \Canterville\Casper
   */
  public function download($filename, $url = null)
  {
    $filename = Helpers::prepareArgument($filename);
    $url = isset($url) ? Helpers::prepareArgument($url) : 'this.getCurrentUrl()';

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.download($url, $filename);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Evaluates an expression in the current page DOM context
   *
   * @param string $code
   * @param array $args
   * @return $this
   */
  public function evaluate($code, array $args = [])
  {
    $args = array_map('Canterville\Utils\Helpers::prepareArgument', $args);
    $argsNames = implode(', ', array_keys($args));
    $argsValues = implode(', ', $args);
    if ($argsValues !== '') {
      $argsValues = ', ' . $argsValues;
    }

    $fragment = <<<FRAGMENT
  casper.evaluate(function($argsNames) {
    $code
  }$argsValues);
FRAGMENT;

    $this->addFragment($fragment);

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
    $selector = Helpers::prepareArgument($selector);
    $values = Helpers::prepareArgument($values);
    $submit = Helpers::prepareArgument($submit);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.fill($selector, $values, $submit);
  });
FRAGMENT;

    $this->addFragment($fragment);

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
    $selector = Helpers::prepareArgument($selector);
    $values = Helpers::prepareArgument($values);
    $submit = Helpers::prepareArgument($submit);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.fillSelectors($selector, $values, $submit);
  });
FRAGMENT;

    $this->addFragment($fragment);

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
    $selector = Helpers::prepareArgument($selector);
    $values = Helpers::prepareArgument($values);
    $submit = Helpers::prepareArgument($submit);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.fillXPath($selector, $values, $submit);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Moves a step forward in browser’s history
   *
   * @return \Canterville\Casper
   */
  public function forward()
  {
    $fragment = <<<FRAGMENT
  casper.forward();
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Retrieves HTML code from the current page
   * By default, it outputs the whole page HTML contents
   * If is specified file, save code, else write it to output
   *
   * @param null|string $filename
   * @param null|string $selector
   * @param bool|false $outer
   * @return $this
   */
  public function getHTML($filename = null, $selector = null, $outer = false)
  {
    $selector = Helpers::prepareArgument($selector);
    $outer = Helpers::prepareArgument($outer);

    $fragmentGetHtml = "this.getHTML($selector, $outer)";

    // write to output
    if (!isset($filename)) {
      $fragment = <<<FRAGMENT
  casper.then(function() {
    this.echo($fragmentGetHtml);
  });
FRAGMENT;
    }
    // save to file
    else {
      $this->useFsModule = true;

      $filename = Helpers::prepareArgument($filename);

      $fragment = <<<FRAGMENT
  casper.then(function() {
    fs.write($filename, $fragmentGetHtml);
    this.echo("[save] HTML to $filename");
  });
FRAGMENT;
    }

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Retrieves current page contents, dealing with exotic other content types than HTML
   *
   * @param null|string $filename
   * @return $this
   */
  public function getPageContent($filename = null)
  {
    $fragmentGetPageContent = "this.getPageContent()";

    // write to output
    if (!isset($filename)) {
      $fragment = <<<FRAGMENT
  casper.then(function() {
    this.echo($fragmentGetPageContent);
  });
FRAGMENT;
    }
    // save to file
    else {
      $this->useFsModule = true;

      $filename = Helpers::prepareArgument($filename);

      $fragment = <<<FRAGMENT
  casper.then(function() {
    fs.write($filename, $fragmentGetPageContent);
    this.echo("[save] page content to $filename");
  });
FRAGMENT;
    }

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Logs a message with an optional level
   *
   * @param string $message
   * @param string $logLevel Default INFO.
   * @return $this
   */
  public function log($message, $logLevel = self::LOG_LEVEL_INFO)
  {
    $message = Helpers::prepareArgument($message);
    $logLevel = Helpers::prepareArgument($logLevel);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.log($message, $logLevel);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Triggers a mouse event on the first element found matching the provided selector
   *
   * @param string $selector
   * @param string $event
   * @return \Canterville\Casper
   * @throws \Canterville\Exception\InvalidArgumentException
   */
  public function mouseEvent($selector, $event)
  {
    $supportedEvents = [
        self::EVENT_CLICK,
        self::EVENT_MOUSE_DOWN,
        self::EVENT_MOUSE_MOVE,
        self::EVENT_MOUSE_OUT,
        self::EVENT_MOUSE_OVER,
        self::EVENT_MOUSE_UP,
    ];

    if (!in_array($event, $supportedEvents)) {
      $msg = sprintf('Mouse event "%s" is is not supported.', $event);
      throw new InvalidArgumentException($msg);
    }

    $selector = Helpers::prepareArgument($selector);
    $event = Helpers::prepareArgument($event);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.mouseEvent($event, $selector);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Performs an HTTP request for opening a given location
   * You can forge GET, POST, PUT, DELETE and HEAD requests in settings
   *
   * @param string $url
   * @param null|array $settings
   * @return \Canterville\Casper
   * @throws \Canterville\Exception\InvalidArgumentException
   */
  public function open($url, array $settings = null)
  {
    $url = Helpers::prepareArgument($url);

    if (isset($settings)) {
      $validKeys = [
          self::OPEN_OPTION_METHOD,
          self::OPEN_OPTION_DATA,
          self::OPEN_OPTION_HEADERS,
      ];
      $invalidKeys = $this->checkValidKeys($validKeys, $settings);

      if (count($invalidKeys) > 0) {
        $msg = sprintf('In parameter $settings is this invalid keys: %s', implode(', ', $invalidKeys));
        throw new InvalidArgumentException($msg);
      }

    }

    $settings = Helpers::prepareArgument($settings);

    $fragment = <<<FRAGMENT
  casper.open($url, $settings);
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Reloads current page location
   *
   * @return \Canterville\Casper
   */
  public function reload()
  {
    $fragment = <<<FRAGMENT
  casper.reload();
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Runs the whole suite of steps
   *
   * @param boolean $preserveScript
   */
  public function run($preserveScript = false)
  {
    $fragment = <<<FRAGMENT
  casper.run();
FRAGMENT;

    $this->addFragment($fragment);

    // for work with files must be required fs module
    if ($this->useFsModule) {
      $fragment = <<<FRAGMENT
  var fs = require('fs');
FRAGMENT;

      $this->shiftFragment($fragment);
    }

    $filename = uniqid('casper-') . '.js';
    FileSystem::write($filename, $this->script);

    $this->doRun($filename);
    $this->logOutput();

    if (!$preserveScript) {
      FileSystem::delete($filename);
    }
  }


  /**
   * Scrolls current document to the coordinates defined by the value of x and y
   *
   * @param int $down
   * @param int $right
   * @return \Canterville\Casper
   */
  public function scrollTo($down, $right = 0)
  {
    $down = Helpers::prepareArgument($down);
    $right = Helpers::prepareArgument($right);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.scrollTo($right, $down);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Scrolls current document to its bottom
   *
   * @return \Canterville\Casper
   */
  public function scrollToBottom()
  {
    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.scrollToBottom();
    this.echo("[scrollToBottom]");
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Sends native keyboard events to the element
   * Supported HTMLElements: <input>, <textarea> and HTMLElement with contenteditable="true"
   *
   * @param string $selector
   * @param string $keys
   * @param array|null $options See constants Casper::SEND_KEYS_OPTION_*
   * @return \Canterville\Casper
   * @throws \Canterville\Exception\InvalidArgumentException
   * Options values:
   *  - RESET: boolean
   *  - KEEP_FOCUS: boolean
   *  - MODIFIERS: array of constants Casper::MODIFIERS_*
   */
  public function sendKeys($selector, $keys, array $options = null)
  {
    $selector = Helpers::prepareArgument($selector);
    $keys = Helpers::prepareArgument($keys);

    if (isset($options)) {
      if (array_key_exists(self::SEND_KEYS_OPTION_MODIFIERS, $options)) {
        if (is_array($options[self::SEND_KEYS_OPTION_MODIFIERS])) {
          $options[self::SEND_KEYS_OPTION_MODIFIERS] = implode('+', $options[self::SEND_KEYS_OPTION_MODIFIERS]);
        }
        else {
          $msg = sprintf(
              'Value in option "%s" must be array, given "%s".',
              self::SEND_KEYS_OPTION_MODIFIERS,
              gettype($options[self::SEND_KEYS_OPTION_MODIFIERS])
          );
          throw new InvalidArgumentException($msg);
        }
      }

    }

    $options = Helpers::prepareArgument($options);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.sendKeys($selector, $keys, $options);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Configures and starts Casper, then open the provided url
   *
   * @param null|string $url
   * @return \Canterville\Casper
   */
  public function start($url = null)
  {
    $this->clean();

    $fragment = <<<FRAGMENT
  var casper = require('casper').create({
    verbose: true,
    pageSettings: {
      javascriptEnabled: true,
      userAgent: '$this->userAgent'
    },
    viewportSize: {
      width: 1280,
      height: 720
    }
  });

  casper.start();
FRAGMENT;

    $this->addFragment($fragment);

    if (isset($url)) {
      $url = Helpers::prepareArgument($url);
      $fragment = <<<FRAGMENT
  casper.then(function() {
    this.open($url, {
      headers: {
        'Accept': 'text/html'
      }
    });
  });
FRAGMENT;

      $this->addFragment($fragment);
    }

    return $this;
  }


  /**
   * Pause steps suite execution for a given amount of time
   *
   * @param int $seconds
   * @return \Canterville\Casper
   */
  public function wait($seconds)
  {
    $mSeconds = Helpers::prepareArgument($seconds * 1000);

    $fragment = <<<FRAGMENT
  casper.wait($mSeconds, function() {
    this.echo('[wait] time $seconds sec occurred');
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Waits until an element matching the provided selector exists in remote DOM
   *
   * @param string $selector
   * @param null|int $maxSeconds
   * @return \Canterville\Casper
   */
  public function waitForSelector($selector, $maxSeconds = null)
  {
    $selector = Helpers::prepareArgument($selector);

    if (isset($maxSeconds)) {
      $maxSeconds *= 1000;
    }
    $maxSeconds = Helpers::prepareArgument($maxSeconds);

    $fragment = <<<FRAGMENT
  casper.waitForSelector($selector,
    function() {
      this.echo("[waitForSelector] element $selector found");
    },
    function() {
      this.echo("[waitForSelector] time for wait on element $selector occurred");
    }, $maxSeconds);
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Waits until the passed text is present in the page contents
   *
   * @param string $text
   * @param null|int $maxSeconds
   * @return \Canterville\Casper
   */
  public function waitForText($text, $maxSeconds = null)
  {
    $text = Helpers::prepareArgument($text);

    if (isset($maxSeconds)) {
      $maxSeconds *= 1000;
    }
    $maxSeconds = Helpers::prepareArgument($maxSeconds);

    $fragment = <<<FRAGMENT
  casper.waitForText($text,
    function() {
      this.echo("[waitForText] text $text found");
    },
    function() {
      this.echo("[waitForText] time for wait on text $text occurred);
    }, $maxSeconds);
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Waits for the current page url to match the provided argument
   *
   * @param string $url Javascript regular expression
   * @param null|int $maxSeconds
   * @return \Canterville\Casper
   */
  public function waitForUrl($url, $maxSeconds = null)
  {
    if (isset($maxSeconds)) {
      $maxSeconds = $maxSeconds * 1000;
    }
    $maxSeconds = Helpers::prepareArgument($maxSeconds);

    $fragment = <<<FRAGMENT
  casper.waitForUrl(/$url/,
    function() {
      this.echo("[waitForUrl] redirected to $url");
    },
    function() {
      this.echo("[waitForUrl] time for wait on URL $url occurred");
    }, $maxSeconds);
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Write something to output
   *
   * @param string $string
   * @param bool|false $evaluate
   * @return $this
   */
  public function write($string, $evaluate = false)
  {
    $string = $evaluate ? $string : Helpers::prepareArgument($string);

    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.echo($string);
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /**
   * Writes current page title to output
   *
   * @return $this
   */
  public function writeTitle()
  {
    $fragment = <<<FRAGMENT
  casper.then(function() {
    this.echo("[title] " + this.getTitle());
  });
FRAGMENT;

    $this->addFragment($fragment);

    return $this;
  }


  /************************** HELPERS **************************/

  /**
   * Added fragment of JS for CasperJS to end
   *
   * @param string $fragment
   */
  private function addFragment($fragment)
  {
    $this->script .= $fragment . PHP_EOL;
  }


  /**
   * Added fragment of JS for CasperJS to begin
   *
   * @param string $fragment
   */
  private function shiftFragment($fragment)
  {
    $this->script = $fragment . PHP_EOL . $this->script;
  }


  /**
   * Clear the current CasperJS script
   */
  private function clean()
  {
    $this->output = [];
    $this->requests = [];
    $this->currentUrl = null;
    $this->script = '';
  }


  /**
   * Logging output
   */
  private function logOutput()
  {
    foreach ($this->output as $outputLine) {
      if (Strings::contains($outputLine, 'Navigation requested:')) {
        $this->logRequest($outputLine);
      }

      foreach ($this->onLog as $debugFunction) {
        call_user_func($debugFunction, $outputLine);
      }
    }
  }


  /**
   * Logging navigation requested
   *
   * @param string $requestLine
   */
  private function logRequest($requestLine)
  {
    $requestLine = Strings::replace($requestLine, '[Navigation requested: ]');
    $requestLine = Strings::trim($requestLine);
    $matches = Strings::matchAll($requestLine, '~ ([^=]+)=([^,]+),?~');
    $request = [];
    foreach ($matches as $match) {
      $match[2] = $match[2] === 'true' ? true : ($match[2] === 'false' ? false : $match[2]);
      $request[$match[1]] = $match[2];
    }
    $this->requests[] = $request;
  }


  /**
   * Check if it's all keys in array are valid
   *
   * @param array $validKeys
   * @param array $field
   * @return array
   */
  private function checkValidKeys(array $validKeys, array $field)
  {
    $invalidKeys = [];

    foreach ($field as $key => $value) {
      if (!in_array($key, $validKeys)) {
        $invalidKeys[] = $key;
      }
    }

    return $invalidKeys;
  }


  /**
   * Run CasperJS and output result
   *
   * @param string $filename
   */
  private function doRun($filename)
  {
    $command = $this->getCommand($filename);

    echo $this->getInfoHeader($command);

    $fp = popen($command, 'r');
    while (!feof($fp)) {
      $line = fread($fp, 1024);

      // skip line with message of PhantomJS bug for non-debug level
      if ($this->getLogLevel() !== self::LOG_LEVEL_DEBUG && Strings::contains($line, 'Unsafe JavaScript attempt to access frame')) {
        continue;
      }

      // skip JS strict warnings for non-debug level
      if ($this->getLogLevel() !== self::LOG_LEVEL_DEBUG && Strings::contains($line, 'JavaScript strict warning:')) {
        continue;
      }

      $line = Strings::replace($line, '[\[phantom\] |\[remote\] ]');
      $line = Strings::normalizeNewLines($line);

      $this->output[] = $line;

      echo $line;
      flush();
    }
    echo PHP_EOL;
    pclose($fp);
  }


  /**
   * Returns command for run
   *
   * @param string $filename
   * @return string
   */
  private function getCommand($filename)
  {
    $commands = [];

    // Canterville binaries has higher priority as user binaries
    $commands[] = 'export PATH=' . $this->getBinDir() . ':$PATH';

    // SlimJS required set path to Firefox
    if ($this->getOption('engine') === self::ENGINE_SLIMMERJS) {
      switch (Helpers::getOS()) {
        case Helpers::OS_MAC:
          $commands[] = 'export SLIMERJSLAUNCHER=/Applications/Firefox.app/Contents/MacOS/firefox';
          break;
        case Helpers::OS_WINDOWS:
          $commands[] = 'SET SLIMERJSLAUNCHER="c:\Program Files\Mozilla Firefox\firefox.exe';
          break;
        case Helpers::OS_LINUX:
          $commands[] = 'export SLIMERJSLAUNCHER=/usr/bin/firefox';
          break;
      }
    }

    // run script on CasperJS with options
    $options = $this->getCommandOptions();
    $commands[] = 'casperjs ' . $filename . $options;

    return implode(';', $commands);
  }


  /**
   * Returns options for command
   *
   * @return string
   */
  private function getCommandOptions()
  {
    $options = '';
    foreach ($this->options as $name => $value) {
      $options .= ' --' . $name;

      if (isset($value)) {
        if (is_bool($value)) {
          $value = $value ? 'yes' : 'no';
        }

        $options .= '=' . $value;
      }
    }

    return $options;
  }


  /**
   * Returns header of run Canterville
   *
   * @param string $command
   * @return string
   */
  private function getInfoHeader($command)
  {
    // casper command is last
    $commands = array_reverse(explode(';', $command));
    $casperCommand = array_shift($commands);

    // parse version from composer.json
    $values = Json::decode(file_get_contents(__DIR__ . '/../../composer.json'));
    $version = $values->version;

    $infoHeader = <<< HEADER
   ____            _                  _ _ _
  / ___|__ _ _ __ | |_ ___ _ ____   _(_) | | ___
 | |   / _` | '_ \| __/ _ \ '__\ \ / / | | |/ _ \
 | |__| (_| | | | | ||  __/ |   \ V /| | | |  __/
  \____\__,_|_| |_|\__\___|_|    \_/ |_|_|_|\___| v$version

run CasperJS: $casperCommand


HEADER;

    return $infoHeader;
  }

}
