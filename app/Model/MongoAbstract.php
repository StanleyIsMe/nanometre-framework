<?php

namespace App\Model;

use MongoClient;

/**
 * abstract Mongo repository
 *
 * @package App\Model
 */
abstract class MongoAbstract
{
    /**
     * @var MongoClient
     */
    protected static $_connection = null;

    /**
     * collection info
     */
    protected $_collection = null;

    /**
     * singleton instance
     */
    public static $_instance = [];

    /**
     * constructor
     * @param MongoClient $connection
     */
    public function __construct(MongoClient $connection = null)
    {
        if (self::$_connection == null) {
            if ($connection !== null) {
                self::$_connection = $connection;
            } else {
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

                self::$_connection = new MongoClient("mongodb://{$host}:{$port}", $option);
            }
        }
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        if (self::$_connection !== null) {
            self::$_connection->close();
        }
    }

    /**
     * Current collection for sub model
     *
     * @return \MongoCollection
     */
    public function table()
    {
        $dbName = getenv(APPLICATION_ENV . 'MONGO_DB_NAME');
        return self::$_connection->{$dbName}->{$this->_collection};
    }

    /**
     * 取得該collection 所有資料
     *
     * @return array
     */
    public function all()
    {
        $result = iterator_to_array($this->table()->find());
        return map($result, function ($val) {
            $val['_id'] = $val['_id']->__toString();
            return $val;
        });
    }
}
