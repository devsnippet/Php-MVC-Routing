<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-07
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;


class Route {
    public static function match($requestUrl) {
        $routingMatcher = new RoutingMatcherMatcher();
        return $routingMatcher->matchUrl($requestUrl);
    }

    public static function getByAction($controller, $action, array $parameters = array()) {

    }

    public static function getBySlug($slug, array $parameters = array()) {

    }
}