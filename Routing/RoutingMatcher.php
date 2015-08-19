<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-06
 * @url <https://github.com/keislamoglu>
 */

namespace Routing;

class RoutingMatcher extends RoutingHelper
{
    /**
     * Routing collection
     * @var RoutingCollection
     */
    private $routingCollection;

    /**
     * Construction
     */
    function __construct()
    {
        $this->routingCollection = new RoutingCollection();
    }

    /**
     * Matches url and return target
     * @param $requestUrl
     * @return array|bool
     */
    public function matchUrl($requestUrl)
    {
        /*
         * Matches directly controller and action accorting to request url
         * Only Parent Routing Definitons are used here from routings
         */
        $requestUrl = '/' . trim($requestUrl, '/');
        if ($theRestOfRequestUrl = $this->matchControllerDirect($requestUrl)) {
            if ($this->matchAction($theRestOfRequestUrl) !== false) {
                $this->getArgsAccortingToUrlPattern($theRestOfRequestUrl);
                $this->getRequestMethods();
                return $this->getTarget();
            }
        }
        /*
         * Matches controller accorting to routing definition which has no parent
         */
        $maxMatchScore = -1;
        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if (!$routingItem->hasParent() && !$routingItem->isParent() && $routingItem->urlPattern()) {
                if ($maxMatchScore < $score = $routingItem->urlPattern()->getMatchScoreUrl($requestUrl)) {
                    $maxMatchScore = $score;
                    $this->controller = $routingItem->getController();
                    $this->action = $routingItem->getAction();
                    $this->urlPattern = $routingItem->urlPattern();
                }
            }
        }
        if (isset($this->controller) && isset($this->action)) {
            $this->getArgsAccortingToUrlPattern($requestUrl);
            $this->getRequestMethods();
            return $this->getTarget();
        }
        /*
         * No matches
         */
        return false;
    }

    /**
     * Matches action by seeking inner specified controller file and routing definitions
     * @param $theRestOfRequestUrl
     * @return bool
     */
    private function matchAction($theRestOfRequestUrl)
    {
        $maxMatchScore = -1;
        /*
         * Seeking in controller class
         */
        foreach ($this->controllerActionNames($this->controller) as $action) {
            // phpDoc Url Pattern
            if ($this->getUrlPatternDefinitionOverActionMethod($this->controller, $action))
                $urlPatternOverActionMethod = new UrlPattern($this->getUrlPatternDefinitionOverActionMethod($this->controller, $action));
            // url pattern created by action name
            $urlPatternCreatedByActionName = new UrlPattern($action);
            // by phpDoc definition over action, if exists
            if (isset($urlPatternOverActionMethod) && $maxMatchScore < $score = $urlPatternOverActionMethod->getMatchScoreUrl($theRestOfRequestUrl)) {
                $maxMatchScore = $score;
                $this->action = $action;
                $this->urlPattern = $urlPatternOverActionMethod;
            } // by action name
            else
                if ($urlPatternCreatedByActionName->getMatchScoreUrl(trim($theRestOfRequestUrl, '/')) !== false) {
                    $this->action = $action;
                    $this->urlPattern = new UrlPattern($this->generateUrlPatternFromActionArgs($this->controller, $this->action));
                }
        }
        if (isset($this->action))
            return true;
        /*
         * Seeking in routings by routing url pattern
         */
        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if ($routingItem->getController() == $this->controller && $routingItem->hasParent()) {
                if ($maxMatchScore < $score = $routingItem->urlPattern()->getMatchScoreUrl($theRestOfRequestUrl)) {
                    $maxMatchScore = $score;
                    $this->action = $routingItem->getAction();
                    $this->urlPattern = $routingItem->urlPattern();
                }
            }
        }
        if (isset($this->action))
            return true;
        /*
         * No matches
         */
        return false;
    }

    /**
     * Matches controller by seeking inner class files and parent routing defition
     * @param $requestUrl
     * @return bool|mixed
     */
    private function matchControllerDirect2($requestUrl)
    {
        /*
         * Seeking in classnames
         */
        foreach ($this->getControllerPathNamesFromClassFiles() as $controllerName) {
            if ($controllerName == 'Mesaj') {
                $test = 2;
            }
            $pureControllerName = str_replace(DIRECTORY_SEPARATOR, '', $controllerName);
            $phpDocUrlPrefix = $this->getUrlPrefixDefinitionOverController($pureControllerName);
            if ($phpDocUrlPrefix !== null && preg_match($pattern = '@^' . $phpDocUrlPrefix . '(?!\w+)@', $requestUrl)) {
                $this->controller = $pureControllerName;
                return preg_replace($pattern, '', $requestUrl, 1); // return the rest of request url
            } else
                if (preg_match($pattern = '@^/' . $controllerName . '(?!\w+)@', $requestUrl)) {
                    $this->controller = $pureControllerName;
                    return preg_replace($pattern, '', $requestUrl, 1); // returns the rest of request url
                }
        }
        /*
         * Seeking in parent routing items
         */
        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if ($routingItem->isParent() && preg_match($pattern = '@^' . $routingItem->getUrlPrefix() . '(?!\w+)@', $requestUrl)) {
                $this->controller = $routingItem->getController();
                return preg_replace('@^' . $routingItem->getUrlPrefix() . '@', '', $requestUrl, 1);
            }
        }
        return false;
    }

    private function matchControllerDirect($requestUrl)
    {
        /*
         * Seeking in classnames
         */
        $maxMatchScore = -1;
        foreach ($this->getControllerPathNamesFromClassFiles() as $controllerName) {
            $pureControllerName = str_replace(DIRECTORY_SEPARATOR, '', $controllerName);
            $phpDocUrlPrefix = $this->getUrlPrefixDefinitionOverController($pureControllerName);
            if ($phpDocUrlPrefix !== null && $maxMatchScore < $score = (new UrlPattern($phpDocUrlPrefix))->getMatchScoreUrl($requestUrl)) {
                $maxMatchScore = $score;
                $pattern = '@^' . $phpDocUrlPrefix . '(?!\w+)@';
                $this->controller = $pureControllerName;
                $theRestOfRequestUrl = preg_replace($pattern, '', $requestUrl, 1); // return the rest of request url
            } else
                if ($maxMatchScore < $score = (new UrlPattern('/' . $controllerName))->getMatchScoreUrl($requestUrl)) {
                    $maxMatchScore = $score;
                    $pattern = '@^/' . $controllerName . '(?!\w+)@';
                    $this->controller = $pureControllerName;
                    $theRestOfRequestUrl = preg_replace($pattern, '', $requestUrl, 1); // returns the rest of request url
                }
        }
        /*
         * Seeking in parent routing items
         */

        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if ($routingItem->isParent() && $maxMatchScore < $score = (new UrlPattern($routingItem->getUrlPrefix()))->getMatchScoreUrl($requestUrl)) {
                $maxMatchScore = $score;
                $pattern = '@^' . $routingItem->getUrlPrefix() . '(?!\w+)@';
                $this->controller = $routingItem->getController();
                $theRestOfRequestUrl = preg_replace($pattern, '', $requestUrl, 1);
            }
        }

        if (isset($this->controller))
            return $theRestOfRequestUrl;
        return false;
    }
}