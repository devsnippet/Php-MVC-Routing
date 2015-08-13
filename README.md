# Php MVC Routing Class
**not stable and not finished yet*

**This class will provide you to:**
- define url pattern just over action methods with phpDoc
- define url prefix pattern just over controller classes with phpDoc
- define routings in a routing file
```php
<?php
/**
 * @UrlPrefix /samplePage
 */
class SampleController extends Controller {
    /**
     * @UrlPattern /sayHelloTo-{username}
     */
    public function helloAction($username) {
        echo "Hello $username!";
    }
}

include_once('loader.php');
$route = new Route();
var_dump($route->match('/samplePage/sayHelloTo-keislamoglu'));
```
The output:
<pre class='xdebug-var-dump' dir='ltr'>
<b>array</b> <i>(size=3)</i>
  'controller' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'SampleController'</font> <i>(length=16)</i>
  'action' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'helloAction'</font> <i>(length=11)</i>
  'args' <font color='#888a85'>=&gt;</font> 
    <b>array</b> <i>(size=1)</i>
      'username' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'keislamoglu'</font> <i>(length=11)</i>
</pre>
