<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

use G4\Tasker\Identifier;

trait CachingTrait
{
    /**
     * @return string
     */
    private function cacheKey()
    {
        return realpath(__FILE__) . Identifier::DELIMITER . self::CACHE_KEY;
    }

    /**
     * @return array|null
     */
    private function fetchFromCache()
    {
        if (!$this->cache) {
            return null;
        }
        $cachedHostnames = $this->cache->key($this->cacheKey())->get();
        if ($cachedHostnames && is_array($cachedHostnames)) {
            return $cachedHostnames;
        }
        return null;
    }

    /**
     * @return void
     */
    private function setToCache(array $hostnames)
    {
        if (!$this->cache) {
            return;
        }
        $this->cache
            ->key($this->cacheKey())
            ->value($hostnames)
            ->expiration($this->hostAvailabilityTime)
            ->set();
    }
}
