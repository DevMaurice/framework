<?php

use Mockery as m;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class SupportCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testFirstReturnsFirstItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertEquals('foo', $c->first());
    }

    public function testLastReturnsLastItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertEquals('bar', $c->last());
    }

    public function testLastWithCallback()
    {
        $data = new Collection([100, 200, 300]);
        $result = $data->last(function ($value) { return $value < 250; });
        $this->assertEquals(200, $result);
        $result = $data->last(function ($value, $key) { return $key < 2; });
        $this->assertEquals(200, $result);
    }

    public function testLastWithCallbackAndDefault()
    {
        $data = new Collection(['foo', 'bar']);
        $result = $data->last(function ($value) { return $value === 'baz'; }, 'default');
        $this->assertEquals('default', $result);
    }

    public function testLastWithDefaultAndWithoutCallback()
    {
        $data = new Collection;
        $result = $data->last(null, 'default');
        $this->assertEquals('default', $result);
    }

    public function testPopReturnsAndRemovesLastItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertEquals('bar', $c->pop());
        $this->assertEquals('foo', $c->first());
    }

    public function testShiftReturnsAndRemovesFirstItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertEquals('foo', $c->shift());
        $this->assertEquals('bar', $c->first());
    }

    public function testEmptyCollectionIsEmpty()
    {
        $c = new Collection();

        $this->assertTrue($c->isEmpty());
    }

    public function testCollectionIsConstructed()
    {
        $collection = new Collection('foo');
        $this->assertSame(['foo'], $collection->all());

        $collection = new Collection(2);
        $this->assertSame([2], $collection->all());

        $collection = new Collection(false);
        $this->assertSame([false], $collection->all());

        $collection = new Collection(null);
        $this->assertSame([], $collection->all());

        $collection = new Collection;
        $this->assertSame([], $collection->all());
    }

    public function testGetArrayableItems()
    {
        $collection = new Collection;

        $class = new ReflectionClass($collection);
        $method = $class->getMethod('getArrayableItems');
        $method->setAccessible(true);

        $items = new TestArrayableObject;
        $array = $method->invokeArgs($collection, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new TestJsonableObject;
        $array = $method->invokeArgs($collection, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new TestJsonSerializeObject;
        $array = $method->invokeArgs($collection, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new Collection(['foo' => 'bar']);
        $array = $method->invokeArgs($collection, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = ['foo' => 'bar'];
        $array = $method->invokeArgs($collection, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);
    }

    public function testToArrayCallsToArrayOnEachItemInCollection()
    {
        $item1 = m::mock('Illuminate\Contracts\Support\Arrayable');
        $item1->shouldReceive('toArray')->once()->andReturn('foo.array');
        $item2 = m::mock('Illuminate\Contracts\Support\Arrayable');
        $item2->shouldReceive('toArray')->once()->andReturn('bar.array');
        $c = new Collection([$item1, $item2]);
        $results = $c->toArray();

        $this->assertEquals(['foo.array', 'bar.array'], $results);
    }

    public function testJsonSerializeCallsToArrayOrJsonSerializeOnEachItemInCollection()
    {
        $item1 = m::mock('JsonSerializable');
        $item1->shouldReceive('jsonSerialize')->once()->andReturn('foo.json');
        $item2 = m::mock('Illuminate\Contracts\Support\Arrayable');
        $item2->shouldReceive('toArray')->once()->andReturn('bar.array');
        $c = new Collection([$item1, $item2]);
        $results = $c->jsonSerialize();

        $this->assertEquals(['foo.json', 'bar.array'], $results);
    }

    public function testToJsonEncodesTheJsonSerializeResult()
    {
        $c = $this->getMock('Illuminate\Support\Collection', ['jsonSerialize']);
        $c->expects($this->once())->method('jsonSerialize')->will($this->returnValue('foo'));
        $results = $c->toJson();

        $this->assertJsonStringEqualsJsonString(json_encode('foo'), $results);
    }

    public function testCastingToStringJsonEncodesTheToArrayResult()
    {
        $c = $this->getMock('Illuminate\Database\Eloquent\Collection', ['jsonSerialize']);
        $c->expects($this->once())->method('jsonSerialize')->will($this->returnValue('foo'));

        $this->assertJsonStringEqualsJsonString(json_encode('foo'), (string) $c);
    }

    public function testOffsetAccess()
    {
        $c = new Collection(['name' => 'taylor']);
        $this->assertEquals('taylor', $c['name']);
        $c['name'] = 'dayle';
        $this->assertEquals('dayle', $c['name']);
        $this->assertTrue(isset($c['name']));
        unset($c['name']);
        $this->assertFalse(isset($c['name']));
        $c[] = 'jason';
        $this->assertEquals('jason', $c[0]);
    }

    public function testArrayAccessOffsetExists()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertTrue($c->offsetExists(0));
        $this->assertTrue($c->offsetExists(1));
        $this->assertFalse($c->offsetExists(1000));
    }

    public function testArrayAccessOffsetGet()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertEquals('foo', $c->offsetGet(0));
        $this->assertEquals('bar', $c->offsetGet(1));
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testArrayAccessOffsetGetOnNonExist()
    {
        $c = new Collection(['foo', 'bar']);
        $c->offsetGet(1000);
    }

    public function testArrayAccessOffsetSet()
    {
        $c = new Collection(['foo', 'foo']);

        $c->offsetSet(1, 'bar');
        $this->assertEquals('bar', $c[1]);

        $c->offsetSet(null, 'qux');
        $this->assertEquals('qux', $c[2]);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testArrayAccessOffsetUnset()
    {
        $c = new Collection(['foo', 'bar']);

        $c->offsetUnset(1);
        $c[1];
    }

    public function testForgetSingleKey()
    {
        $c = new Collection(['foo', 'bar']);
        $c->forget(0);
        $this->assertFalse(isset($c['foo']));

        $c = new Collection(['foo' => 'bar', 'baz' => 'qux']);
        $c->forget('foo');
        $this->assertFalse(isset($c['foo']));
    }

    public function testForgetArrayOfKeys()
    {
        $c = new Collection(['foo', 'bar', 'baz']);
        $c->forget([0, 2]);
        $this->assertFalse(isset($c[0]));
        $this->assertFalse(isset($c[2]));
        $this->assertTrue(isset($c[1]));

        $c = new Collection(['name' => 'taylor', 'foo' => 'bar', 'baz' => 'qux']);
        $c->forget(['foo', 'baz']);
        $this->assertFalse(isset($c['foo']));
        $this->assertFalse(isset($c['baz']));
        $this->assertTrue(isset($c['name']));
    }

    public function testCountable()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertCount(2, $c);
    }

    public function testIterable()
    {
        $c = new Collection(['foo']);
        $this->assertInstanceOf('ArrayIterator', $c->getIterator());
        $this->assertEquals(['foo'], $c->getIterator()->getArrayCopy());
    }

    public function testCachingIterator()
    {
        $c = new Collection(['foo']);
        $this->assertInstanceOf('CachingIterator', $c->getCachingIterator());
    }

    public function testFilter()
    {
        $c = new Collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([1 => ['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->all());

        $c = new Collection(['', 'Hello', '', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->filter()->values()->toArray());

        $c = new Collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
        $this->assertEquals(['first' => 'Hello', 'second' => 'World'], $c->filter(function ($item, $key) {
            return $key != 'id';
        })->all());
    }

    public function testWhere()
    {
        $c = new Collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', '=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', '==', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', 'garbage', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3]],
            $c->where('v', '===', 3)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 4]],
            $c->where('v', '<>', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 4]],
            $c->where('v', '!=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => '3'], ['v' => 4]],
            $c->where('v', '!==', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3']],
            $c->where('v', '<=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->where('v', '>=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2]],
            $c->where('v', '<', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 4]],
            $c->where('v', '>', 3)->values()->all()
        );
    }

    public function testWhereIn()
    {
        $c = new Collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3], ['v' => '3']], $c->whereIn('v', [1, 3])->values()->all());
    }

    public function testWhereInStrict()
    {
        $c = new Collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3]], $c->whereInStrict('v', [1, 3])->values()->all());
    }

    public function testValues()
    {
        $c = new Collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->values()->all());
    }

    public function testFlatten()
    {
        // Flat arrays are unaffected
        $c = new Collection(['#foo', '#bar', '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays are flattened with existing flat items
        $c = new Collection([['#foo', '#bar'], '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Sets of nested arrays are flattened
        $c = new Collection([['#foo', '#bar'], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Deeply nested arrays are flattened
        $c = new Collection([['#foo', ['#bar']], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections are flattened alongside arrays
        $c = new Collection([new Collection(['#foo', '#bar']), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections containing plain arrays are flattened
        $c = new Collection([new Collection(['#foo', ['#bar']]), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections are flattened
        $c = new Collection([['#foo', new Collection(['#bar'])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections containing arrays are flattened
        $c = new Collection([['#foo', new Collection(['#bar', ['#zap']])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], $c->flatten()->all());
    }

    public function testFlattenWithDepth()
    {
        // No depth flattens recursively
        $c = new Collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

        // Specifying a depth only flattens to that depth
        $c = new Collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', ['#bar', ['#baz']], '#zap'], $c->flatten(1)->all());

        $c = new Collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', ['#baz'], '#zap'], $c->flatten(2)->all());
    }

    public function testMergeNull()
    {
        $c = new Collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello'], $c->merge(null)->all());
    }

    public function testMergeArray()
    {
        $c = new Collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello', 'id' => 1], $c->merge(['id' => 1])->all());
    }

    public function testMergeCollection()
    {
        $c = new Collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'World', 'id' => 1], $c->merge(new Collection(['name' => 'World', 'id' => 1]))->all());
    }

    public function testDiffCollection()
    {
        $c = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['id' => 1], $c->diff(new Collection(['first_word' => 'Hello', 'last_word' => 'World']))->all());
    }

    public function testDiffNull()
    {
        $c = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['id' => 1, 'first_word' => 'Hello'], $c->diff(null)->all());
    }

    public function testDiffKeys()
    {
        $c1 = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $c2 = new Collection(['id' => 123, 'foo_bar' => 'Hello']);
        $this->assertEquals(['first_word' => 'Hello'], $c1->diffKeys($c2)->all());
    }

    public function testEach()
    {
        $c = new Collection($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);

        $result = [];
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
        });
        $this->assertEquals($original, $result);

        $result = [];
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
            if (is_string($key)) {
                return false;
            }
        });
        $this->assertEquals([1, 2, 'foo' => 'bar'], $result);
    }

    public function testIntersectNull()
    {
        $c = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals([], $c->intersect(null)->all());
    }

    public function testIntersectCollection()
    {
        $c = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['first_word' => 'Hello'], $c->intersect(new Collection(['first_world' => 'Hello', 'last_word' => 'World']))->all());
    }

    public function testUnique()
    {
        $c = new Collection(['Hello', 'World', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->unique()->all());

        $c = new Collection([[1, 2], [1, 2], [2, 3], [3, 4], [2, 3]]);
        $this->assertEquals([[1, 2], [2, 3], [3, 4]], $c->unique()->values()->all());
    }

    public function testUniqueWithCallback()
    {
        $c = new Collection([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'], 2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'], 4 => ['id' => 4, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'], 6 => ['id' => 6, 'first' => 'Taylor', 'last' => 'Swift'],
        ]);

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
        ], $c->unique('first')->all());

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
        ], $c->unique(function ($item) { return $item['first'].$item['last']; })->all());
    }

    public function testCollapse()
    {
        $data = new Collection([[$object1 = new StdClass], [$object2 = new StdClass]]);
        $this->assertEquals([$object1, $object2], $data->collapse()->all());
    }

    public function testCollapseWithNestedCollactions()
    {
        $data = new Collection([new Collection([1, 2, 3]), new Collection([4, 5, 6])]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $data->collapse()->all());
    }

    public function testSort()
    {
        $data = (new Collection([5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([1, 2, 3, 4, 5], $data->values()->all());

        $data = (new Collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5], $data->values()->all());

        $data = (new Collection(['foo', 'bar-10', 'bar-1']))->sort();
        $this->assertEquals(['bar-1', 'bar-10', 'foo'], $data->values()->all());
    }

    public function testSortWithCallback()
    {
        $data = (new Collection([5, 3, 1, 2, 4]))->sort(function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $this->assertEquals(range(1, 5), array_values($data->all()));
    }

    public function testSortBy()
    {
        $data = new Collection(['taylor', 'dayle']);
        $data = $data->sortBy(function ($x) { return $x; });

        $this->assertEquals(['dayle', 'taylor'], array_values($data->all()));

        $data = new Collection(['dayle', 'taylor']);
        $data = $data->sortByDesc(function ($x) { return $x; });

        $this->assertEquals(['taylor', 'dayle'], array_values($data->all()));
    }

    public function testSortByString()
    {
        $data = new Collection([['name' => 'taylor'], ['name' => 'dayle']]);
        $data = $data->sortBy('name');

        $this->assertEquals([['name' => 'dayle'], ['name' => 'taylor']], array_values($data->all()));
    }

    public function testReverse()
    {
        $data = new Collection(['zaeed', 'alan']);
        $reversed = $data->reverse();

        $this->assertSame([1 => 'alan', 0 => 'zaeed'], $reversed->all());

        $data = new Collection(['name' => 'taylor', 'framework' => 'laravel']);
        $reversed = $data->reverse();

        $this->assertSame(['framework' => 'laravel', 'name' => 'taylor'], $reversed->all());
    }

    public function testFlip()
    {
        $data = new Collection(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['taylor' => 'name', 'laravel' => 'framework'], $data->flip()->toArray());
    }

    public function testChunk()
    {
        $data = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $data = $data->chunk(3);

        $this->assertInstanceOf('Illuminate\Support\Collection', $data);
        $this->assertInstanceOf('Illuminate\Support\Collection', $data[0]);
        $this->assertCount(4, $data);
        $this->assertEquals([1, 2, 3], $data[0]->toArray());
        $this->assertEquals([9 => 10], $data[3]->toArray());
    }

    public function testEvery()
    {
        $data = new Collection([
            6 => 'a',
            4 => 'b',
            7 => 'c',
            1 => 'd',
            5 => 'e',
            3 => 'f',
        ]);

        $this->assertEquals(['a', 'e'], $data->every(4)->all());
        $this->assertEquals(['b', 'f'], $data->every(4, 1)->all());
        $this->assertEquals(['c'], $data->every(4, 2)->all());
        $this->assertEquals(['d'], $data->every(4, 3)->all());
    }

    public function testExcept()
    {
        $data = new Collection(['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com']);

        $this->assertEquals(['first' => 'Taylor'], $data->except(['last', 'email', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], $data->except('last', 'email', 'missing')->all());

        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except(['last'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except('last')->all());
    }

    public function testPluckWithArrayAndObjectValues()
    {
        $data = new Collection([(object) ['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], $data->pluck('email')->all());
    }

    public function testPluckWithArrayAccessValues()
    {
        $data = new Collection([
            new TestArrayAccessImplementation(['name' => 'taylor', 'email' => 'foo']),
            new TestArrayAccessImplementation(['name' => 'dayle', 'email' => 'bar']),
        ]);

        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], $data->pluck('email')->all());
    }

    public function testImplode()
    {
        $data = new Collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertEquals('foobar', $data->implode('email'));
        $this->assertEquals('foo,bar', $data->implode('email', ','));

        $data = new Collection(['taylor', 'dayle']);
        $this->assertEquals('taylordayle', $data->implode(''));
        $this->assertEquals('taylor,dayle', $data->implode(','));
    }

    public function testTake()
    {
        $data = new Collection(['taylor', 'dayle', 'shawn']);
        $data = $data->take(2);
        $this->assertEquals(['taylor', 'dayle'], $data->all());
    }

    public function testRandom()
    {
        $data = new Collection([1, 2, 3, 4, 5, 6]);

        $random = $data->random();
        $this->assertInternalType('integer', $random);
        $this->assertContains($random, $data->all());

        $random = $data->random(3);
        $this->assertInstanceOf('Illuminate\Support\Collection', $random);
        $this->assertCount(3, $random);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRandomThrowsAnErrorWhenRequestingMoreItemsThanAreAvailable()
    {
        (new Collection)->random();
    }

    public function testTakeLast()
    {
        $data = new Collection(['taylor', 'dayle', 'shawn']);
        $data = $data->take(-2);
        $this->assertEquals([1 => 'dayle', 2 => 'shawn'], $data->all());
    }

    public function testMacroable()
    {
        // Foo() macro : unique values starting with A
        Collection::macro('foo', function () {
            return $this->filter(function ($item) {
                    return strpos($item, 'a') === 0;
                })
                ->unique()
                ->values();
        });

        $c = new Collection(['a', 'a', 'aa', 'aaa', 'bar']);

        $this->assertSame(['a', 'aa', 'aaa'], $c->foo()->all());
    }

    public function testMakeMethod()
    {
        $collection = Collection::make('foo');
        $this->assertEquals(['foo'], $collection->all());
    }

    public function testMakeMethodFromNull()
    {
        $collection = Collection::make(null);
        $this->assertEquals([], $collection->all());

        $collection = Collection::make();
        $this->assertEquals([], $collection->all());
    }

    public function testMakeMethodFromCollection()
    {
        $firstCollection = Collection::make(['foo' => 'bar']);
        $secondCollection = Collection::make($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    public function testMakeMethodFromArray()
    {
        $collection = Collection::make(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMakeFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = Collection::make($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMethod()
    {
        $collection = new Collection('foo');
        $this->assertEquals(['foo'], $collection->all());
    }

    public function testConstructMethodFromNull()
    {
        $collection = new Collection(null);
        $this->assertEquals([], $collection->all());

        $collection = new Collection();
        $this->assertEquals([], $collection->all());
    }

    public function testConstructMethodFromCollection()
    {
        $firstCollection = new Collection(['foo' => 'bar']);
        $secondCollection = new Collection($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    public function testConstructMethodFromArray()
    {
        $collection = new Collection(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMethodFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = new Collection($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testSplice()
    {
        $data = new Collection(['foo', 'baz']);
        $data->splice(1);
        $this->assertEquals(['foo'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 0, 'bar');
        $this->assertEquals(['foo', 'bar', 'baz'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 1);
        $this->assertEquals(['foo'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $cut = $data->splice(1, 1, 'bar');
        $this->assertEquals(['foo', 'bar'], $data->all());
        $this->assertEquals(['baz'], $cut->all());
    }

    public function testGetPluckValueWithAccessors()
    {
        $model = new TestAccessorEloquentTestStub(['some' => 'foo']);
        $modelTwo = new TestAccessorEloquentTestStub(['some' => 'bar']);
        $data = new Collection([$model, $modelTwo]);

        $this->assertEquals(['foo', 'bar'], $data->pluck('some')->all());
    }

    public function testMap()
    {
        $data = new Collection(['first' => 'taylor', 'last' => 'otwell']);
        $data = $data->map(function ($item, $key) { return $key.'-'.strrev($item); });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    public function testFlatMap()
    {
        $data = new Collection([
            ['name' => 'taylor', 'hobbies' => ['programming', 'basketball']],
            ['name' => 'adam', 'hobbies' => ['music', 'powerlifting']],
        ]);
        $data = $data->flatMap(function ($person) { return $person['hobbies']; });
        $this->assertEquals(['programming', 'basketball', 'music', 'powerlifting'], $data->all());
    }

    public function testTransform()
    {
        $data = new Collection(['first' => 'taylor', 'last' => 'otwell']);
        $data->transform(function ($item, $key) { return $key.'-'.strrev($item); });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    public function testFirstWithCallback()
    {
        $data = new Collection(['foo', 'bar', 'baz']);
        $result = $data->first(function ($value) { return $value === 'bar'; });
        $this->assertEquals('bar', $result);
    }

    public function testFirstWithCallbackAndDefault()
    {
        $data = new Collection(['foo', 'bar']);
        $result = $data->first(function ($value) { return $value === 'baz'; }, 'default');
        $this->assertEquals('default', $result);
    }

    public function testFirstWithDefaultAndWithoutCallback()
    {
        $data = new Collection;
        $result = $data->first(null, 'default');
        $this->assertEquals('default', $result);
    }

    public function testGroupByAttribute()
    {
        $data = new Collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('rating');
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());

        $result = $data->groupBy('url');
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());
    }

    public function testGroupByAttributePreservingKeys()
    {
        $data = new Collection([10 => ['rating' => 1, 'url' => '1'],  20 => ['rating' => 1, 'url' => '1'],  30 => ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('rating', true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveSingleGroup()
    {
        $data = new Collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        });

        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveSingleGroupPreservingKeys()
    {
        $data = new Collection([10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1'], 30 => ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        }, true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveMultipleGroups()
    {
        $data = new Collection([
            ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        });

        $expected_result = [
            'Role_1' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveMultipleGroupsPreservingKeys()
    {
        $data = new Collection([
            10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            30 => ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        }, true);

        $expected_result = [
            'Role_1' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                30 => ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testKeyByAttribute()
    {
        $data = new Collection([['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']]);

        $result = $data->keyBy('rating');
        $this->assertEquals([1 => ['rating' => 1, 'name' => '1'], 2 => ['rating' => 2, 'name' => '2'], 3 => ['rating' => 3, 'name' => '3']], $result->all());

        $result = $data->keyBy(function ($item) { return $item['rating'] * 2; });
        $this->assertEquals([2 => ['rating' => 1, 'name' => '1'], 4 => ['rating' => 2, 'name' => '2'], 6 => ['rating' => 3, 'name' => '3']], $result->all());
    }

    public function testKeyByClosure()
    {
        $data = new Collection([
            ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ]);
        $result = $data->keyBy(function ($item) {
            return strtolower($item['firstname'].$item['lastname']);
        });
        $this->assertEquals([
            'taylorotwell' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            'lucasmichot'  => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ], $result->all());
    }

    public function testContains()
    {
        $c = new Collection([1, 3, 5]);

        $this->assertTrue($c->contains(1));
        $this->assertFalse($c->contains(2));
        $this->assertTrue($c->contains(function ($value) { return $value < 5; }));
        $this->assertFalse($c->contains(function ($value) { return $value > 5; }));

        $c = new Collection([['v' => 1], ['v' => 3], ['v' => 5]]);

        $this->assertTrue($c->contains('v', 1));
        $this->assertFalse($c->contains('v', 2));

        $c = new Collection(['date', 'class', (object) ['foo' => 50]]);

        $this->assertTrue($c->contains('date'));
        $this->assertTrue($c->contains('class'));
        $this->assertFalse($c->contains('foo'));
    }

    public function testGettingSumFromCollection()
    {
        $c = new Collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
        $this->assertEquals(100, $c->sum('foo'));

        $c = new Collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
        $this->assertEquals(100, $c->sum(function ($i) { return $i->foo; }));
    }

    public function testCanSumValuesWithoutACallback()
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $c->sum());
    }

    public function testGettingSumFromEmptyCollection()
    {
        $c = new Collection();
        $this->assertEquals(0, $c->sum('foo'));
    }

    public function testValueRetrieverAcceptsDotNotation()
    {
        $c = new Collection([
            (object) ['id' => 1, 'foo' => ['bar' => 'B']], (object) ['id' => 2, 'foo' => ['bar' => 'A']],
        ]);

        $c = $c->sortBy('foo.bar');
        $this->assertEquals([2, 1], $c->pluck('id')->all());
    }

    public function testPullRetrievesItemFromCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertEquals('foo', $c->pull(0));
    }

    public function testPullRemovesItemFromCollection()
    {
        $c = new Collection(['foo', 'bar']);
        $c->pull(0);
        $this->assertEquals([1 => 'bar'], $c->all());
    }

    public function testPullReturnsDefault()
    {
        $c = new Collection([]);
        $value = $c->pull(0, 'foo');
        $this->assertEquals('foo', $value);
    }

    public function testRejectRemovesElementsPassingTruthTest()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject('bar')->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject(function ($v) { return $v == 'bar'; })->values()->all());

        $c = new Collection(['foo', null]);
        $this->assertEquals(['foo'], $c->reject(null)->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject('baz')->values()->all());

        $c = new Collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject(function ($v) { return $v == 'baz'; })->values()->all());

        $c = new Collection(['id' => 1, 'primary' => 'foo', 'secondary' => 'bar']);
        $this->assertEquals(['primary' => 'foo', 'secondary' => 'bar'], $c->reject(function ($item, $key) {
            return $key == 'id';
        })->all());
    }

    public function testSearchReturnsIndexOfFirstFoundItem()
    {
        $c = new Collection([1, 2, 3, 4, 5, 2, 5, 'foo' => 'bar']);

        $this->assertEquals(1, $c->search(2));
        $this->assertEquals('foo', $c->search('bar'));
        $this->assertEquals(4, $c->search(function ($value) { return $value > 4; }));
        $this->assertEquals('foo', $c->search(function ($value) { return ! is_numeric($value); }));
    }

    public function testSearchReturnsFalseWhenItemIsNotFound()
    {
        $c = new Collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

        $this->assertFalse($c->search(6));
        $this->assertFalse($c->search('foo'));
        $this->assertFalse($c->search(function ($value) { return $value < 1 && is_numeric($value); }));
        $this->assertFalse($c->search(function ($value) { return $value == 'nope'; }));
    }

    public function testKeys()
    {
        $c = new Collection(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['name', 'framework'], $c->keys()->all());
    }

    public function testPaginate()
    {
        $c = new Collection(['one', 'two', 'three', 'four']);
        $this->assertEquals(['one', 'two'], $c->forPage(1, 2)->all());
        $this->assertEquals([2 => 'three', 3 => 'four'], $c->forPage(2, 2)->all());
        $this->assertEquals([], $c->forPage(3, 2)->all());
    }

    public function testPrepend()
    {
        $c = new Collection(['one', 'two', 'three', 'four']);
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $c->prepend('zero')->all());

        $c = new Collection(['one' => 1, 'two' => 2]);
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $c->prepend(0, 'zero')->all());
    }

    public function testZip()
    {
        $c = new Collection([1, 2, 3]);
        $c = $c->zip(new Collection([4, 5, 6]));
        $this->assertInstanceOf('Illuminate\Support\Collection', $c);
        $this->assertInstanceOf('Illuminate\Support\Collection', $c[0]);
        $this->assertInstanceOf('Illuminate\Support\Collection', $c[1]);
        $this->assertInstanceOf('Illuminate\Support\Collection', $c[2]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4], $c[0]->all());
        $this->assertEquals([2, 5], $c[1]->all());
        $this->assertEquals([3, 6], $c[2]->all());

        $c = new Collection([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7, 8, 9]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c[0]->all());
        $this->assertEquals([2, 5, 8], $c[1]->all());
        $this->assertEquals([3, 6, 9], $c[2]->all());

        $c = new Collection([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c[0]->all());
        $this->assertEquals([2, 5, null], $c[1]->all());
        $this->assertEquals([3, 6, null], $c[2]->all());
    }

    public function testGettingMaxItemsFromCollection()
    {
        $c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(20, $c->max('foo'));

        $c = new Collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(20, $c->max('foo'));

        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $c->max());

        $c = new Collection();
        $this->assertNull($c->max());
    }

    public function testGettingMinItemsFromCollection()
    {
        $c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));

        $c = new Collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));

        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $c->min());

        $c = new Collection();
        $this->assertNull($c->min());
    }

    public function testOnly()
    {
        $data = new Collection(['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com']);

        $this->assertEquals(['first' => 'Taylor'], $data->only(['first', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], $data->only('first', 'missing')->all());

        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->only(['first', 'email'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->only('first', 'email')->all());
    }

    public function testGettingAvgItemsFromCollection()
    {
        $c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(15, $c->avg('foo'));

        $c = new Collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(15, $c->avg('foo'));

        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(3, $c->avg());

        $c = new Collection();
        $this->assertNull($c->avg());
    }

    public function testJsonSerialize()
    {
        $c = new Collection([
            new TestArrayableObject(),
            new TestJsonableObject(),
            new TestJsonSerializeObject(),
            'baz',
        ]);

        $this->assertSame([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            'baz',
        ], $c->jsonSerialize());
    }

    public function testCombineWithArray()
    {
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $c = new Collection(array_keys($expected));
        $actual = $c->combine(array_values($expected))->toArray();

        $this->assertSame($expected, $actual);
    }

    public function testCombineWithCollection()
    {
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $keyCollection = new Collection(array_keys($expected));
        $valueCollection = new Collection(array_values($expected));
        $actual = $keyCollection->combine($valueCollection)->toArray();

        $this->assertSame($expected, $actual);
    }
}

class TestAccessorEloquentTestStub
{
    protected $attributes = [];

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function __get($attribute)
    {
        $accessor = 'get'.lcfirst($attribute).'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }

        return $this->$attribute;
    }

    public function __isset($attribute)
    {
        $accessor = 'get'.lcfirst($attribute).'Attribute';

        if (method_exists($this, $accessor)) {
            return ! is_null($this->$accessor());
        }

        return isset($this->$attribute);
    }

    public function getSomeAttribute()
    {
        return $this->attributes['some'];
    }
}

class TestArrayAccessImplementation implements ArrayAccess
{
    private $arr;

    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function offsetExists($offset)
    {
        return isset($this->arr[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->arr[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->arr[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->arr[$offset]);
    }
}

class TestArrayableObject implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}

class TestJsonableObject implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}

class TestJsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}
