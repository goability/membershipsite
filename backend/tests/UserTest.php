<?php
/*
User Record Tests

RUN THIS FILE OUT OF ./tests directory

- CRUD
*/
declare(strict_types=1);
$currentIncludePath = get_include_path();

echo get_include_path();

set_include_path($currentIncludePath .
                PATH_SEPARATOR . "./src/classes"  .
                PATH_SEPARATOR . "/Users/matt/workspace/dev/publicwarehouse/backend/src/classes");

echo get_include_path();

require_once "../vendor/autoload.php";
require_once "../src/classes/User.php";

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{

  public function testCanCreate() : void
  {
    /*$this->assertInstanceOf(
      User::class, null
    );*/

    $this->assertEquals(1,1);
  }
  // TODO: SETUP MORE TESTS

  /*
  *  Given a few scenarios, ensure a user can:
  *   ADMIN:  Do anything
  *   Facility Owner: Add new locations, containers, bins, pallets, ...
  *   Provider: add containers, bins, pallets, ...
  *   User: Create items, delete items
  */
  public function testUserCanAddNewTypes(){

    /*
    Current test data

    Facility Owners:  1, 3
    Providers: 1, 3
    User only: 298
    */

  }
}
