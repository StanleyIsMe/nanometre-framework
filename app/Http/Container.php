<?php
namespace App\Http;

use ReflectionClass;

/**
 * Container 容器管理
 *
 * @package App\Http
 */
class Container
{
    /**
     * @var static||null
     */
    public static $instance = null;

    /**
     * 儲存物件 instance
     *
     * @var array
     */
    private $instanceArray = [];

    /**
     * 儲存物件別名
     *
     * @var array
     */
    private $aliases = [];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->registerInitAlias();
    }

    /**
     * singleton instance
     *
     * @return \App\Http\Container
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * 登入物件別名
     */
    private function registerInitAlias()
    {
        $this->aliases = [
            'app'       => \App\Http\Application::class,
            'request'   => \App\Http\Request::class,
            'response'  => \App\Http\Response::class,
            'validator' => \App\Http\Validator::class,
            'logger'    => \App\Http\Logger::class
        ];
    }

    /**
     * 注入物件及別名
     *
     * @param string $alias
     * @param string $abstract
     */
    public function registerAlias($alias, $abstract)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * init container status
     */
    public function flush()
    {
        $this->aliases = [];
        $this->instanceArray = [];
    }

    /**
     * 已實例object丟到container
     *
     * @param string $objectName
     * @param object $instance
     */
    public function instance($objectName, $instance)
    {
        if (isset($this->aliases[$objectName])) {
            unset($this->aliases[$objectName]);
        }

        $this->instanceArray[$objectName] = $instance;
    }

    /**
     * 物件實例化array,實現single pattern
     *
     * @param string $alias
     * @param array $parameters
     * @return object
     * @throws \Exception
     */
    public function make($alias, array $parameters = [])
    {
        if (isset($this->instanceArray[$alias])) {
            return $this->instanceArray[$alias];
        }

        if ((isset($this->aliases[$alias]) && class_exists($this->aliases[$alias])) || class_exists($alias)) {
            $objectName = isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;

            $this->instanceArray[$alias] = call_user_func_array([
                new ReflectionClass($objectName),
                'newInstance'
            ], $parameters);
            return $this->instanceArray[$alias];
        } else {
            throw new \Exception("{$alias} is not a object");
        }
    }

    /**
     * 依賴注入，實例物件
     *
     * @param string $objName
     * @param array $parameters
     * @return null|object
     * @throws \Exception
     */
    public function build($objName, array $parameters = [])
    {
        if (isset($this->instanceArray[$objName])) {
            return $this->instanceArray[$objName];
        }

        if (!empty($parameters) || isset($this->aliases[$objName])) {
            return $this->make($objName, $parameters);
        }

        $reflectionClass = new ReflectionClass($objName);

        if (!$reflectionClass->isInstantiable()) {
            throw new \Exception("object {$objName} is not found");
        }

        $reflectionConstructor = $reflectionClass->getConstructor();
        $dependencies = $reflectionConstructor->getParameters();

        $args = [];
        foreach ($dependencies as $param) {
            if ($param->getClass() === null) {
                $args[] = $this->nonClassResolve($param);
            } else {
                $args[] = $this->classResolve($param);
            }

        }
        $this->instanceArray[$objName] = (!empty($args)) ? $reflectionClass->newInstanceArgs($args) : new $objName;

        return $this->instanceArray[$objName];
    }

    /**
     * 非物件參數處理
     *
     * @param \ReflectionParameter $parameter
     * @return array|mixed|null
     */
    protected function nonClassResolve(\ReflectionParameter $parameter)
    {
        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        } elseif ($parameter->isArray()) {
            return [];
        }
        return null;
    }

    /**
     * 物件參數處理
     *
     * @param \ReflectionParameter $parameter
     * @return mixed|null|object
     * @throws \Exception
     */
    protected function classResolve(\ReflectionParameter $parameter)
    {
        try {
            return $this->build($parameter->getClass()->getName());
        } catch (\Exception $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }
}