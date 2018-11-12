<?php
namespace Figured\Resolvers\Interfaces;

/**
 * Type definition constants.
 */
interface Type
{
    const NULL      = 1 << 0;
    const BOOLEAN   = 1 << 1;
    const INTEGER   = 1 << 2;
    const FLOAT     = 1 << 3;
    const STRING    = 1 << 4;
    const ARRAY     = 1 << 5;
    const OBJECT    = 1 << 6;
    const RESOURCE  = 1 << 7;

    /* Combination of all possible types. */
    const MIXED = self::NULL
                | self::BOOLEAN
                | self::INTEGER
                | self::FLOAT
                | self::STRING
                | self::ARRAY
                | self::OBJECT
                | self::RESOURCE;

    /* PHP type lookup from `gettype` to type constant. */
    const LOOKUP = [
        "NULL"     => Type::NULL,
        "boolean"  => Type::BOOLEAN,
        "integer"  => Type::INTEGER,
        "double"   => Type::FLOAT,
        "string"   => Type::STRING,
        "array"    => Type::ARRAY,
        "object"   => Type::OBJECT,
        "resource" => Type::RESOURCE,
    ];
}
