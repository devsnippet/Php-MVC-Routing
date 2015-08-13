<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-06
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;

class RoutingMatcherMatcher extends RoutingMatcherCore {
    /**
     * Matches url and return target
     * @param $requestUrl
     * @throws \Exception
     * @return bool|string
     */
    public function matchUrl($requestUrl) {
        $requestUrl = trim($requestUrl, '/');
        /*
         * Matches directly controller accorting to request url
         * Only Parent Routing Definitons are used here from routings
         */
        if ($theRestOfRequestUrl = $this->matchControllerDirect($requestUrl)) {
            if ($this->matchAction($theRestOfRequestUrl) !== false) {
                $this->getArgsAccortingToUrlPattern($theRestOfRequestUrl);
                return $this->getTarget();
            }
        }
        /*
         * Matches controller accorting to routing definition
         */
        $maxMatchScore = -1;
        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if (!$routingItem->hasParent() && !$routingItem->isParent() && $routingItem->getUrlPattern()) {
                if ($maxMatchScore < $score = $routingItem->getUrlPattern()->getMatchScoreUrl($requestUrl)) {
                    $maxMatchScore = $score;
                    $this->controller = $routingItem->getController();
                    $this->action = $routingItem->getAction();
                    $this->urlPattern = $routingItem->getUrlPattern();
                }
            }
        }
        if (isset($this->controller) && isset($this->action)) {
            $this->getArgsAccortingToUrlPattern($requestUrl);
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
    private function matchAction($theRestOfRequestUrl) {
        $maxMatchScore = -1;
        /*
         * Seeking in controller class
         */
        foreach ($this->controllerActionNames($this->controller) as $action) {
            // phpDoc Url Pattern
            if ($this->getUrlPatternOverActionMethod($this->controller, $action))
                $urlPatternOverActionMethod = new UrlPattern($this->getUrlPatternOverActionMethod($this->controller, $action));
            // url pattern created by action name
            $urlPatternCreatedByActionName = new UrlPattern($action);
            // by phpDoc definition over action, if exists
            if (isset($urlPatternOverActionMethod) && $maxMatchScore < $score = $urlPatternOverActionMethod->getMatchScoreUrl($theRestOfRequestUrl)) {
                $maxMatchScore = $score;
                $this->action = $action;
                $this->urlPattern = $urlPatternOverActionMethod;
            } // by action name
            else
                if ($urlPatternCreatedByActionName->getMatchScoreUrl($theRestOfRequestUrl) !== false) {
                    $this->action = $action;
                    $this->urlPattern = new UrlPattern($this->generateUrlPatternFromActionArgs($this->controller, $this->action));
                }
        }
        if (isset($this->action))
            return true;
        /*
         * Seeking in routings
         */
        foreach ($this->routingCollection->getRoutingItems() as $routingItem) {
            if ($routingItem->getController() == $this->controller && $routingItem->hasParent()) {
                if (preg_match('@^' . $routingItem->getAction() . '(?!\w+)@', $theRestOfRequestUrl)) {
                    $this->action = $routingItem->getAction();
                    $this->urlPattern = $routingItem->getUrlPattern();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Matches controller by seeking inner class files and parent routing defition
     * @param $requestUrl
     * @return bool|mixed
     */
    private function matchControllerDirect($requestUrl) {
        /*
         * Seeking in classnames
         */
        foreach ($this->getControllerPathNamesFromClassFiles() as $controllerName) {
            $pureControllerName = str_replace(DIRECTORY_SEPARATOR, '', $controllerName);
            $phpDocUrlPrefix = ltrim($this->getPhpDocDefUrlPrefix($pureControllerName), '/');
            if ($phpDocUrlPrefix !== null && preg_match($pattern = '@^' . $phpDocUrlPrefix . '(?!\w+)@', $requestUrl)) {
                $this->controller = $pureControllerName;
                return preg_replace($pattern, '', $requestUrl, 1); // return the rest of request url
            } else
                if (preg_match($pattern = '@^' . $controllerName . '(?!\w+)@', $requestUrl)) {
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
                return preg_replace($pattern, '', $requestUrl, 1);
            }
        }
        return false;
    }
}