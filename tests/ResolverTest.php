<?php

use Figured\Resolvers\Cache;
use Figured\Resolvers\Exceptions\ValidationException;
use Figured\Resolvers\Exceptions\MissingDataException;
use Figured\Resolvers\Interfaces\Cache as CacheInterface;
use Figured\Resolvers\Interfaces\Type;
use Figured\Resolvers\Resolver;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TestResolver extends Resolver
{
    public function getA(): int
    {
        return $this->get("a", Type::INTEGER);
    }

    public function getC(): ?string
    {
        return $this->get("b.c", Type::STRING | Type::NULL);
    }

    public function getD($default)
    {
        return $this->get("d", Type::MIXED, $default);
    }

    /**
     * @return TestResolver
     */
    public function getOne(bool $nullable = false)
    {
        return $this->hasOne("ONE", TestResolver::class, $nullable);
    }

    /**
     * @return TestResolver[]
     */
    public function getMany(bool $nullable = false)
    {
        return $this->hasMany("MANY", TestResolver::class, $nullable);
    }
}

/**
 *
 */
class ResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBasicBehaviour()
    {
        $resolver = TestResolver::resolve([
            "a" => 1,
            "b" => [
                "c" => "abc",
            ],
        ]);

        $this->assertEquals(1,     $resolver->getA());
        $this->assertEquals("abc", $resolver->getC());
    }

    public function testResolveHasOne()
    {
        $resolver = TestResolver::resolve([
            "ONE" => [
                "a" => 1,
                "b" => [
                    "c" => "abc",
                ],
            ]
        ]);

        $this->assertEquals(1,     $resolver->getOne()->getA());
        $this->assertEquals("abc", $resolver->getOne()->getC());
    }

    public function testResolveHasMany()
    {
        $resolver = TestResolver::resolve([
            "MANY" => [
                [
                    "a" => 1,
                    "b" => [
                        "c" => "abc",
                    ],
                ],
                [
                    "a" => 2,
                    "b" => [
                        "c" => "xyz",
                    ],
                ]
            ]
        ]);

        $this->assertEquals(1,     $resolver->getMany()[0]->getA());
        $this->assertEquals("abc", $resolver->getMany()[0]->getC());

        $this->assertEquals(2,     $resolver->getMany()[1]->getA());
        $this->assertEquals("xyz", $resolver->getMany()[1]->getC());
    }

    public function testMissingData()
    {
        $resolver = TestResolver::resolve([
            "b" => [
                "c" => 2
            ]
        ]);

        $this->expectException(MissingDataException::class);
        $this->expectExceptionMessage("Missing input data for 'a'");

        $resolver->getA();
    }

    public function testMissingDataNestedParent()
    {
        $resolver = TestResolver::resolve();

        $this->expectException(MissingDataException::class);
        $this->expectExceptionMessage("Missing input data for 'b.c' at 'b'");

        $resolver->getC();
    }

    public function testMissingDataNestedChild()
    {
        $resolver = TestResolver::resolve([
            "b" => [],
        ]);

        $this->expectException(MissingDataException::class);
        $this->expectExceptionMessage("Missing input data for 'b.c' at 'c");

        $resolver->getC();
    }

    public function testWith()
    {
        $input = [
            "a" => 1,
            "b" => [
                "c" => "abc",
            ]
        ];

        $resolver1 = TestResolver::resolve($input);

        $resolver2 = $resolver1->with([
            "a" => 2,
        ]);

        $this->assertEquals(1, $resolver1->getA());
        $this->assertEquals(2, $resolver2->getA());

        $this->assertEquals("abc", $resolver1->getC());
        $this->assertEquals("abc", $resolver2->getC());
    }

    public function testCache()
    {
        $input = [
            "a" => 1,
            "b" => [
                "c" => "abc",
            ]
        ];

        $resolver = new class($input) extends TestResolver
        {
            public function getCache()
            {
                return $this->cache;
            }

            /**
             * @return CacheInterface|MockInterface
             */
            protected function cache(): CacheInterface
            {
                return Mockery::spy(Cache::class)->makePartial();
            }
        };

        $this->assertEquals(1, $resolver->getA());
        $this->assertEquals(1, $resolver->getA());
        $this->assertEquals(1, $resolver->getA());

        $this->assertEquals("abc", $resolver->getC());
        $this->assertEquals("abc", $resolver->getC());
        $this->assertEquals("abc", $resolver->getC());

        $resolver->getCache()->shouldHaveReceived("set", ["a", 1])->once();
        $resolver->getCache()->shouldHaveReceived("set", ["b.c", "abc"])->once();
    }

    public function testGetDefault()
    {
        /* No data returns the default.*/
        $resolver = TestResolver::resolve();
        $this->assertEquals(42, $resolver->getD(42));

        /* Default ignored when data exists. */
        $resolver = $resolver->with(["d" => 10]);
        $this->assertEquals(10, $resolver->getD(42));
    }

    public function testValidationException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unexpected value at 'a'");

        (TestResolver::resolve(["a" => "!"]))->getA();
    }
}
