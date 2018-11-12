<?php
namespace Figured\Resolvers;

class Cache implements Interfaces\Cache
{
    private $store = [];

    /**
     * {@inheritdoc}
     */
    public function has(string $path): bool
    {
        return array_key_exists($path, $this->store);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path)
    {
        return $this->store[$path] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $path, $value)
    {
        $this->store[$path] = $value;
    }
}
