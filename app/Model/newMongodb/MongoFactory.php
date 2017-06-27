<?php

namespace App\Model\NewMongodb;

use MongoDB\Client;

/**
 * repository 工廠
 *
 * @package App\Model
 */
abstract class MongoFactory
{
    /**
     * @var \MongoDB\Client
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
     * @return Client
     */
    private static function getConnection()
    {
        if (self::$connection == null) {
            $host = getenv(APPLICATION_ENV . 'MONGO_HOST');
            $port = getenv(APPLICATION_ENV . 'MONGO_PORT');
            $user = getenv(APPLICATION_ENV . 'MONGO_USER');
            $pass = getenv(APPLICATION_ENV . 'MONGO_PASS');

            $uriOptions = [];
            if (isset($user{0})) {
                $uriOptions["username"] = $user;
            }

            if (isset($pass{0})) {
                $uriOptions["password"] = $pass;
            }

            $driverOptions = [];

            self::$connection = new Client("mongodb://{$host}:{$port}", $uriOptions, $driverOptions);
        }
        return self::$connection;
    }

    /**
     * get repository
     *
     * @param string $table Class 名稱
     * @param string $suffix Class 名的前綴
     *
     * @throws \Exception
     * @return MongoAbstract
     */
    public static function getInstance($table, $suffix = 'Mongo')
    {
        $className = 'App\\Model\\NewMongodb\\' . ucfirst($table) . $suffix;

        if (!class_exists($className)) {
            throw new \Exception("Object {$className} not found");
        }

        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className(self::getConnection());
        }

        return self::$instances[$className];
    }
}
