<?php
/**
 * @author Kadir Emin İslamoğlu <keislamoglu@yandex.com>
 * @date 2015-08-11
 * @url <https://github.com/keislamoglu>
 */

namespace Routing;


class UrlPattern
{
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
    function __construct($urlPattern)
    {
        $this->set($urlPattern);
    }

    /**
     * Returns all arguments array
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Returns absolute arguments array
     * @return array
     */
    public function getAbsoluteArgs()
    {
        return $this->absoluteArgs;
    }

    /**
     * Returns arguments array which might not set
     * @return array
     */
    public function getArgsMightNotSet()
    {
        return $this->argsMightNotSet;
    }

    /**
     * Returns absolutes array
     * @return mixed
     */
    public function getAbsolutes()
    {
        return $this->absolutes;
    }

    /**
     * Returns count arguments
     * @return int
     */
    public function getCountArgs()
    {
        return count($this->args);
    }

    /**
     * Returns count absolute arguments
     * @return int
     */
    public function getCountAbsoluteArgs()
    {
        return count($this->absoluteArgs);
    }

    /**
     * Returns count of arguments might not set
     * @return int
     */
    public function getCountArgsMightNotSet()
    {
        return count($this->argsMightNotSet);
    }

    /**
     * Returns count of absolutes
     * @return int
     */
    public function getCountAbsolutes()
    {
        return count($this->absolutes);
    }

    /**
     * Returns matches score if match, else false
     * @param $requestUrl
     * @return bool|int
     */
    public function getMatchScoreUrl($requestUrl)
    {
        foreach ($this->getRegexPatternVariations() as $regexPattern) {
            if (preg_match($regexPattern, $requestUrl)) {
                return $this->getCountAbsolutes() * 0.5 + $this->getCountAbsoluteArgs() * 0.25 + strlen(implode('', $this->getAbsolutes())) * 0.25;
            }
        }
        return false;
    }

    /**
     * Checks if it's matched with given url
     * @param $requestUrl
     * @return bool
     */
    public function isMatches($requestUrl)
    {
        foreach ($this->getRegexPatternVariations() as $regexPattern) {
            if (preg_match($regexPattern, $requestUrl))
                return true;
        }
        return false;
    }

    /**
     * Checks if url pattern has arguments
     * @return bool
     */
    public function hasArgs()
    {
        return isset($this->args);
    }

    /**
     * Convert and get regex pattern variations
     * @return array
     */
    public function getRegexPatternVariations()
    {
        $variations = array();
        $maxLimit = 0;
        if (preg_match_all($pattern = '@' . '/*{[^}]+\?}@', $this->urlPattern, $argsHasDefaultValueMatches)) {
            $maxLimit = count(current($argsHasDefaultValueMatches));
        }
        for ($limit = $maxLimit; $limit >= 0; $limit--) {
            /*
             * exchange $limit args has default value to willNotRemove string for delete default args from right
             */
            $willNotRemove = 'will' . microtime() . 'not' . microtime() . 'remove';
            $exchangedString = preg_replace('@([^}]+){[^}]+\?}@', '$1' . $willNotRemove, $this->urlPattern, $limit);
            /*
             * Remove args which might not set
             */
            $exchangedString = $this->removeUrlArgsMightNotSet($exchangedString);
            /*
             * Exchange args with regex
             */
            $exchangedString = preg_replace('@' . $willNotRemove . '@', '(.+)', $exchangedString);
            $variations[] = '@^' . preg_replace('@{[^}]+}@', '(.+)', $exchangedString) . '@';
        }
        return $variations;
    }

    /**
     * Returns url pattern string
     * @return mixed
     */
    public function getString()
    {
        return $this->urlPattern;
    }

    /**
     * Set and parse new url pattern string
     * @param $urlPattern
     */
    public function set($urlPattern)
    {
        $this->urlPattern = $urlPattern;
        $this->parse($urlPattern);
    }

    /**
     * Returns url replaced arguments
     * @param array $args
     * @throws \Exception
     * @return mixed
     */
    public function getReplacedStringWithArgs(array $args)
    {
        $replaced = $this->urlPattern;
        foreach ($args as $argKey => $argVal) {
            if (in_array($argKey, $this->getArgs())) {
                $replaced = preg_replace('@{' . $argKey . '\?*}@', $argVal, $replaced);
            } else {
                throw new \Exception('Argument key "' . $argKey . '" is not match with url pattern "' . $this->urlPattern . '"');
            }
        }
        return $this->removeUrlArgs($this->removeUrlArgsMightNotSet($replaced));
    }

    /**
     * parse url pattern
     */
    private function parse()
    {
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

    /**
     * Returns url pattern with removed url args which might not set
     * @param $urlPattern
     * @return mixed
     */
    private function removeUrlArgsMightNotSet($urlPattern)
    {
        return preg_replace('@(?:[^}\w]*){[^}]+\?}@', '', $urlPattern);
    }

    /**
     * Returns url pattern with removed url args
     * @param $urlPattern
     * @return mixed
     */
    private function removeUrlArgs($urlPattern)
    {
        return preg_replace('@(?:[^}\w]*){[^}]+}@', '', $urlPattern);
    }

    /**
     * __toString method
     * @return string
     */
    function __toString()
    {
        return $this->getString();
    }
}