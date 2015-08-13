<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-08
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;


class RoutingCollection {
    /**
     * Routing definitions file path
     */
    const ROUTING_FILE_PATH = ROUTING_FILE_PATH;
    /**
     * @var array
     */
    private $routings;
    /**
     * @var \ArrayObject
     */
    private $routingItems;

    /**
     * Construction
     */
    function __construct() {
        $this->routings = include(self::ROUTING_FILE_PATH);
        $this->routingItems = $this->createRoutingItems($this->routings);
    }

    /**
     * Check by given slug if item exists
     * @param $slug
     * @return bool
     */
    public function routingItemExists($slug) {
        return $this->routingItems->offsetExists($slug);
    }

    /**
     * Get all routing items
     * @return RoutingItem[]
     */
    public function getRoutingItems() {
        return $this->routingItems->getArrayCopy();
    }

    /**
     * Get routing item by given slug
     * @param $slug
     * @return RoutingItem
     */
    public function getRoutingItem($slug) {
        if ($this->routingItemExists($slug))
            return $this->routingItems->offsetGet($slug);
        else
            return null;
    }

    /**
     * Get parent routing item by its parent url prefix
     * @param $urlPrefix
     * @return bool|RoutingItem
     */
    public function getParentItemByUrlPrefix($urlPrefix) {
        /**
         * @var RoutingItem $routingItem
         */
        foreach ($this->routingItems->getArrayCopy() as $slug => $routingItem) {
            if ($routingItem->isParent() && $routingItem->getUrlPrefix() == $urlPrefix) {
                return $routingItem;
            }
        }
        return false;
    }

    /**
     * Delete routing item from collection
     * @param $slug
     */
    public function deleteRoutingItem($slug) {
        if ($this->routingItemExists($slug))
            $this->routingItems->offsetUnset($slug);
    }

    /**
     * Add to collection new routing item
     * @param RoutingItem $routingItem
     * @throws \Exception
     */
    public function addRoutingItem(RoutingItem $routingItem) {
        if (!$this->routingItemExists($routingItem->getSlug())) {
            $this->routingItems->offsetSet($routingItem->getSlug(), $routingItem);
        } else {
            throw new \Exception('Slug "' . $routingItem->getSlug() . '" already exists');
        }
    }

    /**
     * @param $routings
     * @return \ArrayObject
     */
    private function createRoutingItems($routings) {
        $routingItems = new \ArrayObject();
        foreach ($routings as $slug => $properties) {
            $routingItem = new RoutingItem($slug, $properties);
            if ($routingItem->hasParent()) {
                $routingItem->setParentItem(new RoutingItem($routingItem->getParentSlug(), $routings[$routingItem->getParentSlug()]));
            }
            $routingItems->offsetSet($slug, new RoutingItem($slug, $properties));
        }
        return $routingItems;
    }
} 