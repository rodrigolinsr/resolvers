<?php
namespace Figured\Resolvers\Interfaces;

interface Resolver
{
    /**
     * Returns a copy of this resolver's input data merged with given data.
     *
     * @param array $input
     *
     * @return static
     */
    public function with(array $input): Resolver;
}
