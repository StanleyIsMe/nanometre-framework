<?php

namespace App\Model;

use MongoClient;

/**
 * repository 工廠
 *
 * @package App\Model
 */
abstract class MongoFactory
{
    /**
     * @var MongoClient
     */
    private static $connection;

    /**
     * singleton instance
     *
     * @var MongoAbstract[]
     */
    public static $instances = [];

    /**
     * Mongo 建立連線
     * @return MongoClient
     */
    private static function getConnection()
    {
        if (self::$connection == null) {
            $host = getenv(APPLICATION_ENV . 'MONGO_HOST');
            $port = getenv(APPLICATION_ENV . 'MONGO_PORT');
            $user = getenv(APPLICATION_ENV . 'MONGO_USER');
            $pass = getenv(APPLICATION_ENV . 'MONGO_PASS');

            $option = ["connect" => true];

            if (isset($user{0})) {
                $option["username"] = $user;
            }

            if (isset($pass{0})) {
                $option["password"] = $pass;
            }

            self::$connection = new MongoClient("mongodb://{$host}:{$port}", $option);
        }
        return self::$connection;
    }

    /**
     * get repository
     *
     * @param string $table Class 名稱
     * @param string $suffix Class 名的前綴
     *
     * @throws MongoException
     * @return MongoAbstract
     */
    public static function getInstance($table, $suffix = 'Mongo')
    {
        $className = 'App\\Model\\' . ucfirst($table) . $suffix;

        if (!class_exists($className)) {
            throw new MongoException("Object {$className} not found");
        }

        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className(self::getConnection());
        }

        return self::$instances[$className];
    }
}
