<?php
namespace Figured\Resolvers\Interfaces;

interface Cache
{
    /**
     * @param string $path
     *
     * @return mixed
     */
    public function get(string $path);

    /**
     * @param string $path
     *
     * @return bool
     */
    public function has(string $path): bool;

    /**
     * @param string $path
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set(string $path, $value);
}
