<?php
namespace Opine;

class MembershipTest extends \PHPUnit_Framework_TestCase {
    private $membership;

    public function setup () {
        $root = getcwd();
        $container = new Container($root, $root . '/container.yml');
        $this->membership = $container->membership;
    }

    public function testTrueIsTrue() {
        $foo = true;
        $this->assertTrue($foo);
    }
}