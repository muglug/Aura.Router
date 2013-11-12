<?php
namespace Aura\Router;

use ArrayObject;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new RouteFactory;
        $this->server = $_SERVER;
    }
    
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    public function test__isset()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'default' => array(
                'controller' => 'zim',
                'action' => 'dib',
            ),
        ));
        
        $this->assertTrue(isset($route->path));
        $this->assertFalse(isset($route->no_such_property));
    }
    
    public function testIsMatchOnStaticPath()
    {
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'default' => array(
                'controller' => 'zim',
                'action' => 'dib',
            ),
        ));
        
        // right path
        $route = clone $proto;
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('zim', $route->params['controller']);
        $this->assertEquals('dib', $route->params['action']);
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testIsMatchOnDynamicPath()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/{controller}/{action}/{id}{format}',
            'require' => array(
                'controller' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'action' => '([a-zA-Z][a-zA-Z0-9_-]+)',
                'id' => '([0-9]+)',
                'format' => '(\.[^/]+)?',
            ),
            'default' => array(
                'format' => '.html',
            ),
        ));
        
        $actual = $route->isMatch('/foo/bar/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'foo',
            'action' => 'bar',
            'id' => 42,
            'format' => '.html'
        );
        $this->assertEquals($expect, $route->params);
    }
    
    public function testIsMethodMatch()
    {
        $type = 'Aura\Router\Route';
    
        /**
         * try one REQUEST_METHOD
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'require' => array(
                'REQUEST_METHOD' => 'POST',
            ),
        ));
    
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        /**
         * try many REQUEST_METHOD
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'require' => array(
                'REQUEST_METHOD' => 'GET|POST',
            ),
        ));
    
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'POST',
        )));
    
        // wrong path, right REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'GET',
        )));
        
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'REQUEST_METHOD' => 'POST',
        )));
        
        // right path, wrong REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'REQUEST_METHOD' => 'PUT',
        )));
        
        // no REQUEST_METHOD
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array()));
    }
    
    public function testIsSecureMatch_https()
    {
        $type = 'Aura\Router\Route';
        
        /**
         * secure required
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'secure' => true,
        ));
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'on',
        )));
        
        // not secure
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        /**
         * not-secure required
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'secure' => false,
        ));
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'off',
        )));
        
        // secured when it should not be
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'HTTPS' => 'on',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'HTTPS' => 'off',
        )));
    }
    
    public function testIsSecureMatch_serverPort()
    {
        $type = 'Aura\Router\Route';
        
        /**
         * secure required
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'secure' => true,
        ));
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '443',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'SERVER_PORT' => '443',
        )));
        
        // not secure
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '80',
        )));
        
        /**
         * not-secure required
         */
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'secure' => false,
        ));
        
        // correct
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '80',
        )));
        
        // secured when it should not be
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/foo/bar/baz', array(
            'SERVER_PORT' => '443',
        )));
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', array(
            'SERVER_PORT' => '80',
        )));
    }
    
    public function testIsCustomMatchWithClosure()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'is_match' => function($server, ArrayObject $matches) {
                $matches['zim'] = 'gir';
                return true;
            },
        ));
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->params['zim']);
        
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'is_match' => function($server, $matches) {
                return false;
            },
        ));
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function testIsCustomMatchWithCallback()
    {
        $type = 'Aura\Router\Route';
        
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'is_match' => array($this, 'callbackForIsMatchTrue'),
        ));
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        $this->assertEquals('gir', $route->params['zim']);
        
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'is_match' => array($this, 'callbackForIsMatchFalse'),
        ));
        
        // even though path is correct, should fail because of the closure
        $this->assertFalse($route->isMatch('/foo/bar/baz', $this->server));
    }
    
    public function callbackForIsMatchTrue(array $server, ArrayObject $matches)
    {
        $matches['zim'] = 'gir';
        return true;
    }
    
    public function callbackForIsMatchFalse(array $server, ArrayObject $matches)
    {
        return false;
    }
    
    /**
     * This test should not get exception for the urlencode on closure
     */
    public function testGenerateControllerAsClosureIssue19()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/blog/{id}/edit',
            'require' => array(
                'id' => '([0-9]+)',
            ),
            'default' => array(
                "controller" => function ($params) {
                    $id = (int) $params['id'];
                    return "Hello World";
                },
                'action' => 'read',
                'format' => '.html',
            ),
        ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }
    
    public function testGenerate()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/blog/{id}/edit',
            'require' => array(
                'id' => '([0-9]+)',
            ),
        ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/42/edit', $url);
    }
    
    public function testGenerateWithClosure()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/blog/{id}/edit',
            'require' => array(
                'id' => '([0-9]+)',
            ),
            'generate' => function($route, $data) {
                $data['id'] = 99;
                return $data;
            }
        ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }
    
    public function testGenerateWithCallback()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/blog/{id}/edit',
            'require' => array(
                'id' => '([0-9]+)',
            ),
            'generate' => array($this, 'callbackForGenerate'),
        ));
        
        $url = $route->generate(array('id' => 42, 'foo' => 'bar'));
        $this->assertEquals('/blog/99/edit', $url);
    }
    
    public function testGenerateWithWildcard()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/blog/{id}',
            'require' => array(
                'id' => '([0-9]+)',
            ),
            'wildcard' => 'other',
        ));
        
        $url = $route->generate(array(
            'id' => 42,
            'foo' => 'bar',
            'other' => array(
                'dib' => 'zim',
                'irk' => 'gir',
            ),
        ));
            
        $this->assertEquals('/blog/42/zim/gir', $url);
    }
    
    public function testGenerateWithOptional()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/archive/{category}{/year,month,day}',
        ));
        
        $url = $route->generate(array(
            'category' => 'foo',
            'year' => '1979',
            'month' => '11',
        ));
        
        $this->assertEquals('/archive/foo/1979/11', $url);
    }
    
    public function callbackForGenerate(\Aura\Router\Route $route, array $data)
    {
        $data['id'] = 99;
        return $data;
    }
    
    public function testIsMatchOnDefaultAndDefinedSubpatterns()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/{controller}/{action}/{id}{format}',
            'require' => array(
                'action' => '(browse|read|edit|add|delete)',
                'id' => '(\d+)',
                'format' => '(\.[^/]+)?',
            ),
        ));
        
        $actual = $route->isMatch('/any-value/read/42', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'any-value',
            'action' => 'read',
            'id' => '42',
            'format' => null
        );
        $this->assertSame($expect, $route->params);
    }
    
    public function testIsNotRoutable()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/foo/bar/baz',
            'default' => array(
                'controller' => 'zim',
                'action' => 'dib',
            ),
            'routable' => false,
        ));
        
        // right path
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertFalse($actual);
        
        // wrong path
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
    
    public function testGenerateOnFullUri()
    {
        $route = $this->factory->newRoute(array(
            'name' => 'google-search',
            'path' => 'http://google.com/?q={q}',
            'routable' => false,
            'path_prefix' => '/foo/bar', // SHOULD NOT show up
        ));
        
        $actual = $route->generate(array('q' => "what's up doc?"));
        $expect = "http://google.com/?q=what%27s%20up%20doc%3F";
        $this->assertSame($expect, $actual);
    }
   
    public function testGenerateRFC3986()
    {
        $route = $this->factory->newRoute(array(
            'name' => 'rfc3986',
            'path' => '/path/{string}',
            'routable' => false,
        ));
   
        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $actual = $route->generate(array('string' => 'foo @+%/'));
        $expect = '/path/foo%20%40%2B%25%2F';
        $this->assertSame($actual, $expect);
   
        $actual = $route->generate(array('string' => 'sales and marketing/Miami'));
        $expect = '/path/sales%20and%20marketing%2FMiami';
        $this->assertSame($actual, $expect);        
    }
   
    public function testIsMatchOnRFC3986Paths()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/{controller}/{action}/{param1}/{param2}',
        ));
        
        // examples taken from http://php.net/manual/en/function.rawurlencode.php
        $actual = $route->isMatch('/some-controller/some%20action/foo%20%40%2B%25%2F/sales%20and%20marketing%2FMiami', $this->server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'some-controller',
            'action' => 'some action',
            'param1' => 'foo @+%/',
            'param2' => 'sales and marketing/Miami',
        );
        $this->assertEquals($expect, $route->params);
    }
   
   public function testGithubIssue7()
   {
        $server = array(
            'DOCUMENT_ROOT' => '/media/Linux/auracomponentstest',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 49850,
            'SERVER_SOFTWARE' => 'PHP 5.4.0RC5-dev Development Server',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 8000,
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => '/media/Linux/auracomponentstest/index.php',
            'PHP_SELF' => '/index.php',
            'HTTP_HOST' => 'localhost:8000',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'REQUEST_TIME' => '1327369518.2441',
        );
        
        $route = $this->factory->newRoute(array(
            'path' => '/blog/read/{id}{format}',
            'require' => array(
                'id' => '(\d+)',
                'format' => '(\.json|\.html)?',
            ),
            'default' => array(
                'controller' => 'blog',
                'action' => 'read',
                'format' => '.html',
            ),
        ));
         
        $actual = $route->isMatch('/blog/read/42.json', $server);
        $this->assertTrue($actual);
        $expect = array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 42,
            'format' => '.json'
        );
        $this->assertEquals($expect, $route->params);
   }
   
   public function testIsMatchOnlWildcard()
   {
        $proto = $this->factory->newRoute(array(
            'path' => '/foo/{zim}/',
            'wildcard' => 'wild',
        ));
        
        // right path with wildcard values
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/baz/dib', $this->server));
        $this->assertSame('bar', $route->params['zim']);
        $this->assertSame(array('baz', 'dib'), $route->params['wild']);
        
        // right path with trailing slash but no wildcard values
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar/', $this->server));
        $this->assertSame('bar', $route->params['zim']);
        $this->assertSame(array(), $route->params['wild']);
        
        // right path without trailing slash
        $route = clone $proto;
        $this->assertTrue($route->isMatch('/foo/bar', $this->server));
        $this->assertSame(array(), $route->params['wild']);
        
        // wrong path
        $route = clone $proto;
        $this->assertFalse($route->isMatch('/zim/dib/gir', $this->server));
    }
   
    public function testIsMatchOnOptionalParams()
    {
        $route = $this->factory->newRoute(array(
            'path' => '/foo/{bar}{/baz,dib,zim}',
        ));
        
        $actual = $route->isMatch('/foo', $this->server);
        $this->assertFalse($actual);
        
        $actual = $route->isMatch('/foo/bar', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar/baz', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar/baz/dib', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar/baz/dib/zim', $this->server);
        $this->assertTrue($actual);
        
        $actual = $route->isMatch('/foo/bar/baz/dib/zim/gir', $this->server);
        $this->assertFalse($actual);
   }
}
