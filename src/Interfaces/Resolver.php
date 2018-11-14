<?php
namespace Figured\Resolvers\Interfaces;

interface Resolver
{
    /**
     * @param array $input
     * @param mixed ...$args
     *
     * @return mixed
     */
    public static function resolve(array $input = []): Resolver;

    /**
     * Returns a copy of this resolver's input data merged with given data.
     *
     * @param array $input
     *
     * @return static
     */
    public function with(array $input): Resolver;
}
