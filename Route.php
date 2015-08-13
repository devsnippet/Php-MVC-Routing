<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-07
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;


class Route {
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
     * @return array|bool
     */
    public static function match($requestUrl) {
        $routingMatcher = new RoutingMatcher();
        return $routingMatcher->matchUrl($requestUrl);
    }

    /**
     * @param $controller
     * @param $action
     * @param array $parameters
     */
    public static function getByAction($controller, $action, array $parameters = array()) {

    }

    /**
     * @param $slug
     * @param array $parameters
     */
    public static function getBySlug($slug, array $parameters = array()) {

    }
}