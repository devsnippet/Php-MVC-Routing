<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-07
 * @url <https://github.com/keislamoglu>
 */

namespace Routing;


use System\Engine\Request;

class Route extends RoutingHelper
{
    /**
     * Returns matched target array
     * #sample output:
     * array(
     *      'controller' => 'SampleController',
     *      'action' => 'sampleAction',
     *      'args' => array(
     *          'username' => 'keislamoglu'
     *      )
     * )
     * @param $requestUrl
     * @throws \Exception
     * @return array|bool
     */
    public static function match($requestUrl)
    {
        $routingMatcher = new RoutingMatcher();
        $target = $routingMatcher->matchUrl($requestUrl);
        if (self::isValid($target)) {
            return $target;
        } else {
            throw new \Exception('Invalid Url For Requests "' . Request::getRequestMethod() . '"');
        }
    }

    /**
     * @param $controllerName
     * @param $actionName
     * @param array $args
     * @return mixed
     */
    public static function getByAction($controllerName, $actionName, array $args = array())
    {
        $instance = new self;
        $routingCollection = new RoutingCollection();
        /*
         * controller url prefix
         */
        if ($phpDocUrlPrefix = $instance->getUrlPrefixDefinitionOverController($controllerName)) {
            $urlPrefix = $phpDocUrlPrefix;
        } else if ($parentRoutingItem = $routingCollection->getParentItemByController($controllerName)) {
            $urlPrefix = $parentRoutingItem->getUrlPrefix();
        } /*
         * if matches a full routing definition, return it
         */
        else if ($routingItem = $routingCollection->find($controllerName, $actionName)) {
            return $routingItem->urlPattern()->getReplacedStringWithArgs($args);
        } else {
            $urlPrefix = '/' . $controllerName;
        }
        /*
         * if a url prefix has been found, continue with find action url pattern
         * action url pattern
         */
        if ($urlPrefix) {
            if ($phpDocUrlPattern = $instance->getUrlPatternDefinitionOverActionMethod($controllerName, $actionName)) {
                $urlPattern = $phpDocUrlPattern;
            } else if (isset($parentRoutingItem)) {
                foreach ($routingCollection->getChilds($parentRoutingItem->getSlug()) as $childRoutingItem) {
                    if ($childRoutingItem->getAction() == $actionName) {
                        $urlPattern = $childRoutingItem->urlPattern()->getString();
                        break;
                    }
                }
            }
        }
        if (isset($urlPattern)) {
            return (new UrlPattern($urlPrefix . $urlPattern))->getReplacedStringWithArgs($args);
        } else {
            return (new UrlPattern($urlPrefix . $instance->generateUrlPatternFromActionArgs($controllerName, $actionName)))->getReplacedStringWithArgs($args);
        }
    }

    /**
     * Returns url matched with given slug
     * @param $slug
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public static function get($slug, array $args = array())
    {
        $instance = new self;
        $routingCollection = new RoutingCollection();
        if (!$routingItem = $routingCollection->getRoutingItem($slug)) {
            throw new \Exception('Couldn\'t find routing definition for slug "' . $slug . '"');
        }
        if (!$routingItem->isParent()) {
            if ($routingItem->urlFullPattern() !== null) {
                return $routingItem->urlFullPattern()->getReplacedStringWithArgs($args);
            } else {
                return '/' . $routingItem->getController() . $instance->generateUrlPatternFromActionArgs($routingItem->getController(), $routingItem->getAction());
            }
        } else {
            throw new \Exception('Routing definition shouldn\'t be parent to generate url; slug "' . $routingItem->getSlug() . '" is a parent definition');
        }
    }

    /**
     * Checks if action method is valid for this request
     * @param array $target
     * @return bool
     */
    private static function isValid(array &$target)
    {
        if (in_array(Request::getRequestMethod(), $target['request_methods'])) {
            return true;
        } else {
            $postMethodName = preg_replace('@(\w+)' . self::ActionSuffix . '@', '$1' . ucfirst(Request::getRequestMethod()), $target['action'], 1);
            if (is_callable($target['controller'], $postMethodName)) {
                $target['action'] = $postMethodName;
                return true;
            } else {
                return false;
            }
        }
    }
}