<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-06
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;


class RoutingItem {
    const MetaRoutingController = 'controller';
    const MetaRoutingAction = 'action';
    const MetaRoutingParameters = 'params';
    const MetaRoutingUrlPattern = 'url_pattern';
    const MetaRoutingUrlPrefix = 'url_prefix';
    const MetaRoutingParentSlug = 'parent_slug';
    /**
     * @var string
     */
    private $slug;
    /**
     * @var string
     */
    private $parentSlug;
    /**
     * @var string
     */
    private $urlPrefix;
    /**
     * @var string
     */
    private $action;
    /**
     * @var string
     */
    private $controller;
    /**
     * @var string
     */
    private $urlPattern;
    /**
     * @var RoutingItem
     */
    private $parentItem;

    public function __construct($slug, array $properties) {
        $this->createInstance($slug, $properties);
    }

    /**
     * Create a routing item instance
     * @param $slug
     * @param array $properties
     */
    public function createInstance($slug, array $properties) {
        $this->slug = $slug;
        $this->setProperties($properties);
    }

    /**
     * Returns defined action
     * @return string|null
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Returns defined controller
     * @return string|null
     */
    public function getController() {
        if (isset($this->controller))
            return $this->controller;
        else {
            return $this->getParentItem()->getController();
        }
    }

    /**
     * Returns defined url pattern
     * @return null|UrlPattern
     */
    public function urlPattern() {
        if (isset($this->urlPattern))
            return new UrlPattern(trim($this->urlPattern, '/'));
        else
            return null;
    }

    /**
     * Returns defined url pattern, if it has parent, add parent prefix url
     * @return null|UrlPattern
     */
    public function getFullUrlPattern() {
        if (isset($this->urlPattern)) {
            $urlPattern = $this->hasParent() ? $this->getParentItem()->getUrlPrefix() : '';
            $urlPattern .= $this->urlPattern;
            return new UrlPattern(trim($urlPattern, '/'));
        } else {
            return null;
        }
    }

    /**
     * Checks if this is a parent
     * @return bool
     */
    public function isParent() {
        if (isset($this->urlPrefix))
            return true;
        else
            return false;
    }

    /**
     * Checks if this has a parent slug
     * @return bool
     */
    public function hasParent() {
        if (isset($this->parentSlug))
            return true;
        else
            return false;
    }

    /**
     * Returns url prefix if this is a parent; else null
     * @return string|null
     */
    public function getUrlPrefix() {
        if (isset($this->urlPrefix))
            return trim($this->urlPrefix, '/');
        else
            return null;
    }

    /**
     * Returns slug
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Returns parent slug if this has a parent; else null
     * @return string|null
     */
    public function getParentSlug() {
        return $this->parentSlug;
    }

    /**
     * Set parent item
     * @param RoutingItem $routingItem
     * @throws \Exception
     */
    public function setParentItem(RoutingItem $routingItem) {
        if (!$this->isParent())
            $this->parentItem = $routingItem;
        else
            throw new \Exception('You can not set a parent for slug "' . $this->getSlug() . '"');
    }

    /**
     * Return parent routing item if this has;else null
     * @return RoutingItem|null
     */
    public function getParentItem() {
        if ($this->hasParent()) {
            if (!$this->parentItem) {
                $routingCollection = new RoutingCollection();
                $this->parentItem = $routingCollection->getRoutingItem($this->parentSlug);
            }
            return $this->parentItem;
        } else {
            return null;
        }
    }

    /**
     * Exhange url prefix with controller in request url
     * @param $requestUrl
     * @return mixed
     */
    public function exchangeUrlPrefixWithController($requestUrl) {
        return preg_replace('@' . $this->getUrlPrefix() . '@', $this->getController(), $requestUrl, 1);
    }

    /**
     * Setting properties
     * @param array $properties
     * @throws \Exception
     */
    public function setProperties(array $properties) {
        $this->controller = isset($properties[self::MetaRoutingController]) ? $properties[self::MetaRoutingController] : null;
        $this->action = isset($properties[self::MetaRoutingAction]) ? $properties[self::MetaRoutingAction] : null;
        $this->urlPrefix = isset($properties[self::MetaRoutingUrlPrefix]) ? $properties[self::MetaRoutingUrlPrefix] : null;
        $this->parentSlug = isset($properties[self::MetaRoutingParentSlug]) ? $properties[self::MetaRoutingParentSlug] : null;
        $this->urlPattern = isset($properties[self::MetaRoutingUrlPattern]) ? $properties[self::MetaRoutingUrlPattern] : null;
        $this->validateRoutingDefition();
    }

    /**
     * Validate if it's a valid definition
     * @throws \Exception
     */
    private function validateRoutingDefition() {
        $case_1 = !$this->hasParent() && !$this->getController(); // controller should be defined
        $case_2 = !$this->isParent() && !$this->getAction(); // action should be defined
        $case_3 = $this->isParent() && !$this->getController(); // controller should be defined
//        $case_4 = !$this->getUrlPattern() && !$this->getUrlPrefix(); // url pattern should be defined
        if ($case_1 || $case_2 || $case_3 /*|| $case_4*/) {
            $message = 'Bad routing definition for slug "' . $this->slug . '".';
            if ($case_1 || $case_3)
                $message .= ' Controller';
            if ($case_2)
                $message .= ' Action';
            /*  if ($case_4)
                  $message .= ' Url Pattern';*/
            $message .= ' should be defined.';
            throw new \Exception($message);
        }
    }
} 