<?php

use PHPUnit\Framework\TestCase;

use function Phantasy\Types\{sum, product};

class FunctionsTest extends TestCase
{
    public function testProduct()
    {
        ini_set('xdebug.overload_var_dump', 0);
        $Point3D = product('Point3D', ['x', 'y', 'z']);

        $a = $Point3D(1, 2, 3);
        $this->assertEquals($a->x, 1);
        $this->assertEquals($a->y, 2);
        $this->assertEquals($a->z, 3);

        $Point3D->scale = function ($n) {
            return $this->Point3D($n * $this->x, $n * $this->y, $n * $this->z);
        };

        $b = $a->scale(2);
        $this->assertEquals($b->x, 2);
        $this->assertEquals($b->y, 4);
        $this->assertEquals($b->z, 6);

        ob_start();
        echo $a;
        $d = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($d, 'Point3D(1, 2, 3)');
    }

    public function testProductWithNotDefinedFunction()
    {
        $Foo = product('Foo', ['x', 'y']);
        $a = $Foo(1, 2);

        $this->assertNull($a->test());
    }

    /**
     * @expectedException Exception
     */
    public function testProductWithWrongNumberOfArguments()
    {
        $Foo = product('Foo', ['x', 'y']);
        $a = $Foo(12);
    }

    public function testProductWithFunction()
    {
        $Foo = product('Foo', ['run']);

        $Foo->map = function ($f) use ($Foo) {
            $run = $this->run;
            return $Foo(function () use ($run, $f) {
                return $f($run());
            });
        };

        $a = $Foo(function () {
            return 12;
        });

        $this->assertEquals(
            $a->map(function ($x) {
                return $x + 1;
            })->run(),
            13
        );
    }

    /**
     * @expectedException Exception
     */
    public function testSumCataWithoutAllTheCases()
    {
        $Foo = sum('Foo', [
            'A' => ['x', 'y'],
            'B' => []
        ]);

        $Foo->test = function () {
            return $this->cata([]);
        };

        $a = $Foo->A(1, 2);
        $a->test();
    }

    /**
     * @expectedException Exception
     */
    public function testSumCataWithoutTheProperCases()
    {
        $Foo = sum('Foo', [
            'A' => ['x', 'y'],
            'B' => []
        ]);

        $Foo->test = function () {
            return $this->cata([
                'C' => function () {
                    return 'foo';
                },
                'D' => function () {
                    return 'bar';
                }
            ]);
        };

        $a = $Foo->A(1, 2);
        $a->test();
    }

    public function testSumCataWithUnderscoreCase()
    {
        $Foo = sum('Foo', [
            'A' => ['x', 'y'],
            'B' => []
        ]);

        $Foo->test = function () {
            return $this->cata([
                'C' => function () {
                    return 'foo';
                },
                '_' => function () {
                    return 'bar';
                }
            ]);
        };

        $a = $Foo->A(1, 2);
        $b = $Foo->B();
        $this->assertEquals($a->test(), 'bar');
        $this->assertEquals($b->test(), 'bar');
    }

    public function testSumWithUndefinedMethod()
    {
        $Foo = sum('Foo', [
            'A' => [],
            'B' => []
        ]);

        $this->assertNull($Foo->test());

        $a = $Foo->A();
        $b = $Foo->B();

        $this->assertNull($a->test());
        $this->assertNull($b->test());
    }

    public function testOptionSum()
    {
        $option = sum('Option', [
            'Some' => ['x'],
            'None' => []
        ]);
        ob_start();
        echo $option;
        $d = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($d, 'Option');

        $a = $option->Some(1);
        $b = $option->None();

        $option->map = function ($f) {
            return $this->cata([
                'Some' => function ($x) use ($f) {
                    return $this->Some($f($x));
                },
                'None' => function () {
                    return $this->None();
                }
            ]);
        };

        $c = $a->map(function ($x) {
            return $x + 1;
        });
        $d = $b->map(function ($x) {
            return $x + 1;
        });
        $this->assertEquals($option->Some(2), $c);
        $this->assertEquals($option->None(), $d);
    }

    public function testMultipleValueSum()
    {
        $foo = sum('Foo', [
            'A' => ['a', 'b'],
            'B' => ['c', 'd']
        ]);

        $a = $foo->A('foo', 'bar');
        $b = $foo->B('foo', 'baz');

        ob_start();
        echo $a;
        $d = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($d, "Foo.A('foo', 'bar')");

        $foo->map = function ($f) {
            return $this->cata([
                'A' => function ($a, $b) use ($f) {
                    return $this->A($f($a), $f($b));
                },
                'B' => function ($c, $d) use ($f) {
                    return $this->B($f($c), $d);
                }
            ]);
        };

        $c = $a->map(function ($x) {
            return $x . 'test';
        });
        $d = $b->map(function ($x) {
            return $x . 'tester';
        });

        $this->assertEquals($foo->A('footest', 'bartest'), $c);
        $this->assertEquals($foo->B('footester', 'baz'), $d);
    }
}
