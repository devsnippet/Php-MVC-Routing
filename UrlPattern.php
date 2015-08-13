<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-11
 * @url <https://github.com/keislamoglu>
 */

namespace System\Engine\Routing;


class UrlPattern {
    /**
     * Url pattern string
     * @var string
     */
    private $urlPattern;
    /**
     * Absolutes
     * @var array
     */
    private $absolutes = array();
    /**
     * All arguments
     * @var array
     */
    private $args = array();
    /**
     * Absolute arguments
     * @var array
     */
    private $absoluteArgs = array();
    /**
     * Arguments which might not set
     * @var array
     */
    private $argsMightNotSet = array();

    /**
     * Construction
     * @param $urlPattern
     */
    function __construct($urlPattern) {
        $this->set($urlPattern);
    }

    /**
     * Returns all arguments array
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }

    /**
     * Returns absolute arguments array
     * @return array
     */
    public function getAbsoluteArgs() {
        return $this->absoluteArgs;
    }

    /**
     * Returns arguments array which might not set
     * @return array
     */
    public function getArgsMightNotSet() {
        return $this->argsMightNotSet;
    }

    /**
     * Returns absolutes array
     * @return mixed
     */
    public function getAbsolutes() {
        return $this->absolutes;
    }

    /**
     * Returns count arguments
     * @return int
     */
    public function getCountArgs() {
        return count($this->args);
    }

    /**
     * Returns count absolute arguments
     * @return int
     */
    public function getCountAbsoluteArgs() {
        return count($this->absoluteArgs);
    }

    /**
     * Returns count of arguments might not set
     * @return int
     */
    public function getCountArgsMightNotSet() {
        return count($this->argsMightNotSet);
    }

    /**
     * Returns count of absolutes
     * @return int
     */
    public function getCountAbsolutes() {
        return count($this->absolutes);
    }

    /**
     * Returns matches score if match, else false
     * @param $requestUrl
     * @return bool|int
     */
    public function getMatchScoreUrl($requestUrl) {
        foreach ($this->getRegexPatternVariations() as $regexPattern) {
            if (preg_match($regexPattern, $requestUrl))
                return $this->getCountAbsolutes() * 0.8 + $this->getCountAbsoluteArgs() * 0.2;
        }
        return false;
    }

    /**
     * Checks if url pattern has arguments
     * @return bool
     */
    public function hasArgs() {
        return isset($this->args);
    }

    /**
     * Slice url pattern by regex pattern
     * @param $regexPattern
     */
    public function sliceByRegex($regexPattern) {
        $regexPattern = '@(' . trim($regexPattern, '/@%~;') . ')@';
        $this->urlPattern = preg_replace($regexPattern, '$1', $this->urlPattern, 1);
    }

    /**
     * Convert and get regex pattern variations
     * @return array
     */
    public function getRegexPatternVariations() {
        $variations = array();
        $maxLimit = 0;
        if (preg_match_all($pattern = '@' . '/*{[^}]+\?}@', $this->urlPattern, $argsHasDefaultValueMatches)) {
            $maxLimit = count(current($argsHasDefaultValueMatches));
        }
        for ($limit = $maxLimit; $limit >= 0; $limit--) {
            /*
             * exchange $limit args has default value to [willNotRemove] string for delete default args from right
             */
            $exchangedString = preg_replace('@([^}]+){[^}]+\?}@', '$1[willNotRemove]', $this->urlPattern, $limit);
            /*
             * Remove args has default value
             */
            $exchangedString = preg_replace('@([^}]+){[^}]+\?}@', '', $exchangedString);
            /*
             * Exchange args with regex
             */
            $exchangedString = preg_replace('@\[willNotRemove\]@', '(.+)', $exchangedString);
            $variations[] = '@' . preg_replace('@{[^}]+}@', '(.+)', $exchangedString) . '@';
        }
        return $variations;
    }

    /**
     * Returns url pattern string
     * @return mixed
     */
    public function get() {
        return $this->urlPattern;
    }

    /**
     * Set and parse new url pattern string
     * @param $urlPattern
     */
    public function set($urlPattern) {
        $this->urlPattern = $urlPattern;
        $this->parse($urlPattern);
    }

    /**
     * parse url pattern
     */
    private function parse() {
        if (preg_match_all('@{(\w+)\?*}(?!\w+)@', $this->urlPattern, $matchesArgs)) {
            $this->args = next($matchesArgs);
        }
        if (preg_match_all('@{(\w+)}(?!\w+)@', $this->urlPattern, $matchesAbsoluteArgs)) {
            $this->absoluteArgs = next($matchesAbsoluteArgs);
        }
        if (preg_match_all('@{(\w+)\?}(?!\w+)@', $this->urlPattern, $matchesArgsMightNotSet)) {
            $this->argsMightNotSet = next($matchesArgsMightNotSet);
        }
        if (preg_match_all('@(?<!{)([\w-]+)(?!(?:\?|)})(?!\w+)@', $this->urlPattern, $matchesAbsolutes)) {
            $this->absolutes = next($matchesAbsolutes);
        }
    }
}