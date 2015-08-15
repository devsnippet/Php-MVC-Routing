<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-06
 * @url <https://github.com/keislamoglu>
 */

namespace Routing;


use System\Helper\ClassHelper;
use System\Helper\FileHelper;
use System\Helper\PhpDocParser;

class RoutingHelper {
    /**
     * Controller class files directory
     */
    const CONTROLLER_FILES_DIRECTORY = DIR_CONTROLLER; // controller class files directory
    /**
     * Contoller class name's suffix
     */
    const ControllerSuffix = 'Controller';
    /**
     * Action method name's suffix
     */
    const ActionSuffix = 'Action';
    /**
     * PhpDoc url pattern definition key which is over action method
     */
    const MetaPhpUrlPattern = '@UrlPattern';
    /**
     * PhpDoc url pattern definition key which is over action method
     */
    const MetaPhpDocRoutingSlug = '@RoutingSlug';
    /**
     * PhpDoc url prefix definition key which is over controller class
     */
    const MetaPhpDocUrlPrefix = '@UrlPrefix';
    /**
     * Controller name
     * @var string
     */
    protected $controller;
    /**
     * Action name
     * @var string
     */
    protected $action;
    /**
     * Arguments array
     * @var array
     */
    protected $args = array();
    /**
     * Url pattern object
     * @var UrlPattern
     */
    protected $urlPattern;

    /**
     * Generate controller class ful name with namespace
     * @param $controller
     * @return string
     */
    protected function controllerClassFullName($controller) {
        $controller = preg_replace('@(\w+)' . self::ControllerSuffix . '@', '$1', str_replace('/', '', $controller));
        return 'Projects\\' . PROJECT_NAME . '\\App\\' . $controller . self::ControllerSuffix;
    }

    /**
     * Generate action method full name
     * @param $action
     * @return string
     */
    protected function actionMethodFullName($action) {
        $action = preg_replace('@(\w+)' . self::ActionSuffix . '@', '$1', $action);
        return $action . self::ActionSuffix;
    }

    /**
     * Check if controller class exists
     * @param $controllerName
     * @return bool
     */
    protected function controllerExists($controllerName) {
        return class_exists($this->controllerClassFullName($controllerName));
    }

    /**
     * Get phpDoc UrlPattern value which is over action method if exists, else returns null
     * @param $controllerName
     * @param $actionName
     * @return null|string
     */
    protected function getUrlPatternOverActionMethod($controllerName, $actionName) {
        return PhpDocParser::methodDoc($this->controllerClassFullName($controllerName), $this->actionMethodFullName($actionName), self::MetaPhpUrlPattern);
    }

    /**
     * Get phpDoc RoutingSlug value which is over action method if exists, else returns null
     * @param $controllerName
     * @param $actionName
     * @return null|string
     */
    protected function getRoutingSlugOverActionMethod($controllerName, $actionName) {
        return PhpDocParser::methodDoc($this->controllerClassFullName($controllerName), $this->actionMethodFullName($actionName), self::MetaPhpDocRoutingSlug);
    }

    /**
     * Returns given Controller's action methods
     * @param $controllerName
     * @return array
     */
    protected function controllerActionNames($controllerName) {
        return array_map(function ($method) {
            return preg_replace('@(\w+)' . self::ActionSuffix . '@', '$1', $method, 1);
        }, array_filter(ClassHelper::getMethods($this->controllerClassFullName($controllerName)), function ($method) {
            return preg_match('@\w+' . self::ActionSuffix . '@', $method);
        }));
    }

    /**
     * Returns controller, action and arguments array
     * @return array
     */
    protected function getTarget() {
        return [
            'controller' => $this->controllerClassFullName($this->controller),
            'action' => $this->actionMethodFullName($this->action),
            'args' => $this->args
        ];
    }

    /**
     * Returns Action's argument names
     * @param $controllerName
     * @param $actionName
     * @return array
     */
    protected function getActionArgNames($controllerName, $actionName) {
        return ClassHelper::getMethodArgNames($this->controllerClassFullName($controllerName), $this->actionMethodFullName($actionName));
    }

    /**
     * Returns Action's arguments
     * @param $controllerName
     * @param $actionName
     * @return \ReflectionParameter[]
     */
    protected function getActionArgs($controllerName, $actionName) {
        return ClassHelper::getMethodArgs($this->controllerClassFullName($controllerName), $this->actionMethodFullName($actionName));
    }

    /**
     * Get arguments accorting to url pattern from request url
     * @param $requestUrl
     */
    protected function getArgsAccortingToUrlPattern($requestUrl) {
        if ($this->urlPattern->hasArgs()) {
            $unsortedArgValues = $this->parseArgs($requestUrl);
            $argNames = $this->getActionArgNames($this->controller, $this->action);
            if (!empty($argNames)) {
                foreach ($argNames as $argName) {
                    if (isset($unsortedArgValues[$argName]))
                        $this->args[$argName] = $unsortedArgValues[$argName];
                }
            }
        }
    }

    /**
     * Parse and return arguments from request url
     * @param $requestUrl
     * @return mixed
     */
    protected function parseArgs($requestUrl) {
        $args = array();
        foreach ($this->urlPattern->getRegexPatternVariations() as $regexPattern) {
            if (preg_match($regexPattern, $requestUrl, $matchesRequestUrl)) {
                $matchesUrlPattern = $this->urlPattern->getArgs();
                foreach (array_splice($matchesRequestUrl, 1) as $key => $match) {
                    $args[$matchesUrlPattern[$key]] = $match;
                }
                break;
            }
        }
        return $args;
    }

    /**
     * Get controller names cutted suffixes from class files
     * @return array
     */
    protected function getControllerPathNamesFromClassFiles() {
        return array_map(function ($v) {
            return preg_replace('@' . self::CONTROLLER_FILES_DIRECTORY . DIRECTORY_SEPARATOR . '([\w+/]+)' . self::ControllerSuffix . '\.php@', '$1', $v);
        }, FileHelper::directoryToArray(self::CONTROLLER_FILES_DIRECTORY));
    }

    /**
     * Get Controller UrlPrefix PhpDoc Definition
     * @param $controllerName
     * @return null|string
     */
    protected function getPhpDocDefUrlPrefix($controllerName) {
        return PhpDocParser::classDoc($this->controllerClassFullName($controllerName), self::MetaPhpDocUrlPrefix);
    }

    /**
     * Generate default url pattern looking action
     * #example: sampleAction($arg1, $arg2) => sample/{arg1?}/{arg2?}
     * @param $controllerName
     * @param $actionName
     * @return string
     */
    protected function generateUrlPatternFromActionArgs($controllerName, $actionName) {
        return '/' . $actionName . '/' . implode('/', array_map(function ($arg) {
            $argPattern = '{' . $arg->getName();
            $argPattern .= $arg->isOptional() ? '?}' : '}';
            return $argPattern;
        }, $this->getActionArgs($controllerName, $actionName)));
    }
} 