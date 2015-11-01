<?php
/**
 * @author Ladislav Vondráček <lad.von@gmail.com>
 * @package Tests
 */

namespace Tests;

require_once __DIR__ . '/bootstrap.php';

use Canterville\Utils as CU;
use Nette;
use Tester;
use Tester\Assert;

class Helpers extends Tester\TestCase
{

  public function testGetOS()
  {
    $options = [null, CU\Helpers::OS_MAC, CU\Helpers::OS_LINUX, CU\Helpers::OS_WINDOWS];
    Assert::contains(CU\Helpers::getOS(), $options);
  }


  public function testGetBitSize()
  {
    $options = [null, CU\Helpers::BIT_32, CU\Helpers::BIT_64];
    Assert::contains(CU\Helpers::getBitSize(), $options);
  }


  /**
   * @dataProvider dataPrepareArgument
   */
  public function testPrepareArgument($value, $expected)
  {
    Assert::same($expected, CU\Helpers::prepareArgument($value));
  }


  public function dataPrepareArgument()
  {
    $array = ['foo', 'bar'];
    $json = '[
    "foo",
    "bar"
]';

    $arrayIndex = [
        'foo' => 'bar',
        'baz'
    ];
    $object = (object)$arrayIndex;
    $jsonObject = '{
    "foo": "bar",
    "0": "baz"
}';

    return [
      [null, 'undefined'],
      [true, 'true'],
      [false, 'false'],
      [-1, -1],
      [0, 0],
      [1, 1],
      [-0.1, -0.1],
      [0.1, 0.1],
      ['string', "'string'"],
      [$array, $json],
      [$arrayIndex, $jsonObject],
      [$object, $jsonObject],
    ];
  }

}

(new Helpers())->run();
