<?php

use Figured\Resolvers\Interfaces\Type;
use Figured\Resolvers\Types;
use Figured\Resolvers\Validator;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function typesDataProvider()
    {
        return [

            /* Basic, single types. */
            [Type::NULL,        null,       true],
            [Type::BOOLEAN,     true,       true],
            [Type::BOOLEAN,     false,      true],
            [Type::INTEGER,     1,          true],
            [Type::FLOAT,       0.1,        true],
            [Type::STRING,      "abc",      true],
            [Type::ARRAY,       [1],        true],
            [Type::OBJECT,      $this,      true],

            /* NULL is not valid for any of them, except NULL. */
            [Type::BOOLEAN,     null,       false],
            [Type::BOOLEAN,     null,       false],
            [Type::INTEGER,     null,       false],
            [Type::FLOAT,       null,       false],
            [Type::STRING,      null,       false],
            [Type::ARRAY,       null,       false],
            [Type::OBJECT,      null,       false],

            /* First type failing should not break. */
            [Type::INTEGER | Type::BOOLEAN,     true,       true],
            [Type::INTEGER | Type::BOOLEAN,     false,      true],
            [Type::INTEGER | Type::INTEGER,     1,          true],
            [Type::INTEGER | Type::FLOAT,       0.1,        true],
            [Type::INTEGER | Type::STRING,      "abc",      true],
            [Type::INTEGER | Type::ARRAY,       [1],        true],
            [Type::INTEGER | Type::OBJECT,      $this,      true],
        ];
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testTypes(int $types, $input, bool $expected)
    {
        /* Test that the type given is valid. */
        $this->assertEquals($expected, (new Validator())->isValid($input, $types));

        /* Test that mixed matches whatever value was given. */
        $this->assertTrue(Types::isValid(Type::MIXED, $input));

        /* Test that allowing NULL allows NULL no matter what type of value was given. */
        $this->assertTrue(Types::isValid($types | Type::NULL, $input));
    }
}
