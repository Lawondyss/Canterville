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

  private $options = array();


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
   * @return array
   */
  public function getOptions()
  {
    return $this->options;
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


  /**
   * Check if it's all keys in array are valid
   *
   * @param array $validKeys
   * @param array $field
   * @return array
   */
  private function checkValidKeys(array $validKeys, array $field)
  {
    $invalidKeys = array();

    foreach ($field as $key => $value) {
      if (!in_array($key, $validKeys)) {
        $invalidKeys[] = $key;
      }
    }

    return $invalidKeys;
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
    if (!isset($area)) {
      $areaFragment = 'undefined';
    }
    else {
      $validKeys = array(
        self::CAPTURE_AREA_TOP,
        self::CAPTURE_AREA_LEFT,
        self::CAPTURE_AREA_WIDTH,
        self::CAPTURE_AREA_HEIGHT,
      );
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

      $areaFragment = Json::encode($area);
    }

    $optionsFragment = isset($options) ? Json::encode($options) : 'undefined';

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
   * Saves a remote resource onto the filesystem
   *
   * @param string $filename
   * @param null|string $url If null then download current page
   * @return \Canterville\Casper
   */
  public function download($filename, $url = null)
  {
    $urlFragment = isset($url) ? "'$url'" : 'this.getCurrentUrl()';

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.download($urlFragment, '$filename');
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Moves a step forward in browser’s history
   *
   * @return \Canterville\Casper
   */
  public function forward()
  {
    $fragment =
<<<FRAGMENT
  casper.forward();

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
   * Triggers a mouse event on the first element found matching the provided selector
   *
   * @param string $selector
   * @param string $event
   * @return \Canterville\Casper
   * @throws \Canterville\InvalidArgumentException
   */
  public function mouseEvent($selector, $event)
  {
    $supportedEvents = array(
      self::EVENT_CLICK,
      self::EVENT_MOUSE_DOWN,
      self::EVENT_MOUSE_MOVE,
      self::EVENT_MOUSE_OUT,
      self::EVENT_MOUSE_OVER,
      self::EVENT_MOUSE_UP,
    );

    if (!in_array($event, $supportedEvents)) {
      $msg = sprintf('Mouse event "%s" is is not supported.', $event);
      throw new InvalidArgumentException($msg);
    }

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.mouseEvent('$event', '$selector');
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Performs an HTTP request for opening a given location
   * You can forge GET, POST, PUT, DELETE and HEAD requests in settings
   *
   * @param string $url
   * @param null|array $settings
   * @return \Canterville\Casper
   * @throws \Canterville\InvalidArgumentException
   */
  public function open($url, array $settings = null)
  {
    if (!isset($settings)) {
      $settingsFragment = 'undefined';
    }
    else {
      $validKeys = array(
        self::OPEN_OPTION_METHOD,
        self::OPEN_OPTION_DATA,
        self::OPEN_OPTION_HEADERS,
      );
      $invalidKeys = $this->checkValidKeys($validKeys, $settings);

      if (count($invalidKeys) > 0) {
        $msg = sprintf('In parameter $settings is this invalid keys: %s', implode(', ', $invalidKeys));
        throw new InvalidArgumentException($msg);
      }

      $settingsFragment = Json::encode($settings);
    }

    $fragment =
<<<FRAGMENT
  casper.open('$url', $settingsFragment);

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Reloads current page location
   *
   * @return \Canterville\Casper
   */
  public function reload()
  {
    $fragment =
<<<FRAGMENT
  casper.reload();

FRAGMENT;

    $this->script .= $fragment;

    return $this;
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
    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.scrollTo($right, $down);
  });

FRAGMENT;

    $this->script .= $fragment;

    return $this;
  }


  /**
   * Scrolls current document to its bottom
   *
   * @return \Canterville\Casper
   */
  public function scrollToBottom()
  {
    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.scrollToBottom();
  });

FRAGMENT;

    $this->script .= $fragment;

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
   * @throws \Canterville\InvalidArgumentException
   *
   * Options values:
   *  - RESET: boolean
   *  - KEEP_FOCUS: boolean
   *  - MODIFIERS: array of constants Casper::MODIFIERS_*
   */
  public function sendKeys($selector, $keys, array $options = null)
  {
    if (!isset($options)) {
      $optionsFragment = 'undefined';
    }
    else {
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

      $optionsFragment = Json::encode($options);
    }

    $fragment =
<<<FRAGMENT
  casper.then(function() {
    this.sendKeys('$selector', '$keys', $optionsFragment);
  });

FRAGMENT;

    $this->script .= $fragment;

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

    $fragment =
<<<FRAGMENT
  var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    colorizerType: 'Dummy',
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

    $this->script .= $fragment;

    if (isset($url)) {
      $openFragment =
<<<OPENFRAGMENT
  casper.then(function() {
    this.open('$url', {
      headers: {
        'Accept': 'text/html'
      }
    });
  });

OPENFRAGMENT;
      $this->script .= $openFragment;
    }

    return $this;
  }


  /**
   * Runs the whole suite of steps
   *
   * @param boolean $preserveScript
   */
  public function run($preserveScript = false)
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

    $commands = array(
      'export PATH=' . $this->getBinDir() . ':$PATH',
      'casperjs ' . $filename . $options,
    );

    exec(implode('; ', $commands), $this->output);
    $this->processOutput();

    if ($preserveScript) {
      unlink($filename);
    }
  }

}
