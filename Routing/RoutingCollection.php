<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-08
 * @url <https://github.com/keislamoglu>
 */

namespace Routing;


class RoutingCollection extends RoutingHelper {
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
    private static $routingItems;

    /**
     * Construction
     */
    function __construct() {
        $this->routings = include(self::ROUTING_FILE_PATH);
        self::$routingItems = $this->parseRoutingDefinitions($this->routings);
        $this->catchRoutingDefinitions();
    }

    /**
     * Check by given slug if item exists
     * @param $slug
     * @return bool
     */
    public function routingItemExists($slug) {
        return self::$routingItems->offsetExists($slug);
    }

    /**
     * Get all routing items
     * @return RoutingItem[]
     */
    public function getRoutingItems() {
        return self::$routingItems->getArrayCopy();
    }

    /**
     * Find a routing item by its controller and action definition
     * @param $controllerName
     * @param $actionName
     * @return null|RoutingItem
     */
    public function find($controllerName, $actionName) {
        foreach ($this->getRoutingItems() as $routingItem) {
            if (!$routingItem->hasParent() && $routingItem->getController() == $controllerName && $routingItem->getAction() == $actionName) {
                return $routingItem;
            }
        }
        return null;
    }

    /**
     * Get child routing items by their parent slug
     * @param $parentSlug
     * @return RoutingItem[]
     */
    public function getChilds($parentSlug) {
        $childRoutingItems = array();
        foreach ($this->getRoutingItems() as $routingItem) {
            if ($routingItem->getParentSlug() == $parentSlug) {
                $childRoutingItems[] = $routingItem;
            }
        }
        return $childRoutingItems;
    }

    /**
     * Get routing item by given slug
     * @param $slug
     * @return null|RoutingItem
     */
    public function getRoutingItem($slug) {
        if ($this->routingItemExists($slug))
            return self::$routingItems->offsetGet($slug);
        else
            return null;
    }

    /**
     * Get parent routing item by its parent url prefix
     * @param $urlPrefix
     * @return null|RoutingItem
     */
    public function getParentItemByUrlPrefix($urlPrefix) {
        /**
         * @var RoutingItem $routingItem
         */
        foreach (self::$routingItems->getArrayCopy() as $slug => $routingItem) {
            if ($routingItem->isParent() && $routingItem->getUrlPrefix() == $urlPrefix) {
                return $routingItem;
            }
        }
        return null;
    }

    /**
     * Returns parent routing item if given controller is matched
     * @param $controllerName
     * @return null|RoutingItem
     */
    public function getParentItemByController($controllerName) {
        foreach ($this->getRoutingItems() as $routingItem) {
            if ($routingItem->isParent() && $routingItem->getController() == $controllerName) {
                return $routingItem;
            }
        }
        return null;
    }

    /**
     * Delete routing item from collection
     * @param $slug
     */
    public function deleteRoutingItem($slug) {
        if ($this->routingItemExists($slug))
            self::$routingItems->offsetUnset($slug);
    }

    /**
     * Add to collection new routing item
     * @param RoutingItem $routingItem
     * @throws \Exception
     */
    public function addRoutingItem(RoutingItem $routingItem) {
        if (!$this->routingItemExists($routingItem->getSlug())) {
            self::$routingItems->offsetSet($routingItem->getSlug(), $routingItem);
        } else {
            throw new \Exception('Slug "' . $routingItem->getSlug() . '" already exists');
        }
    }

    /**
     * Parse and set routing definitions in routing file
     * @param $routings
     * @return \ArrayObject
     */
    private function parseRoutingDefinitions($routings) {
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

    /**
     * Catch routing definitions from controller files (slug definition is required for catching)
     */
    private function catchRoutingDefinitions() {
        foreach ($this->getControllerPathNamesFromClassFiles() as $controllerName) {
            if (!$urlPrefix = $this->getPhpDocDefUrlPrefix($controllerName)) {
                $urlPrefix = '/' . $controllerName;
            }
            foreach ($this->controllerActionNames($controllerName) as $actionName) {
                if ($routingSlug = $this->getRoutingSlugOverActionMethod($controllerName, $actionName)) {
                    $urlPattern = $this->getUrlPatternOverActionMethod($controllerName, $actionName);
                    $routingItem = new RoutingItem($routingSlug, [
                        RoutingItem::MetaRoutingAction => $actionName,
                        RoutingItem::MetaRoutingController => $controllerName,
                        RoutingItem::MetaRoutingUrlPattern => $urlPrefix . $urlPattern
                    ]);
                    $this->addRoutingItem($routingItem);
                }
            }
        }
    }
} 