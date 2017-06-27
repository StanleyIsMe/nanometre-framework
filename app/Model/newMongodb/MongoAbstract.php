<?php

namespace App\Model\NewMongodb;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\UTCDateTime;
/**
 * abstract Mongo repository
 *
 * @package App\Model
 */
abstract class MongoAbstract
{
    /**
     * @var \MongoDB\Client
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
     * @var null mongo time object
     */
    protected $createAt;

    /**
     * @var null mongo time object
     */
    protected $deleteAt;

    /**
     * @var null mongo time object
     */
    protected $updateAt;


    /**
     * constructor
     * @param Client $connection
     */
    public function __construct(Client $connection = null)
    {
        if (self::$_connection == null) {
            if ($connection !== null) {
                self::$_connection = $connection;
            } else {
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
        }

        $mongoTimeObj = new \MongoDB\BSON\UTCDateTime(time() * 1000);
        $this->createAt = $mongoTimeObj;
        $this->updateAt = $mongoTimeObj;
        $this->deleteAt = $mongoTimeObj;
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        self::$_connection = null;
    }

    /**
     * Current collection for sub model
     *
     * @param string|null $collection
     * @return Collection
     */
    public function table($collection = null)
    {
        $dbName = getenv(APPLICATION_ENV . 'MONGO_DB_NAME');

        if ($collection === null) {
            return self::$_connection->{$dbName}->{$this->_collection};
        }

        return self::$_connection->{$dbName}->{$collection};
    }

    /**
     * 取得該collection 所有資料
     *
     * @return array
     */
    public function all()
    {
        $result = $this->table()->find([], [
            'typeMap' => [
                'array'    => 'array',
                'root'     => 'array',
                'document' => 'array'
            ]
        ]);

        return map($result, function ($val) {
            $val['_id'] = $val['_id']->__toString();
            return $val;
        });
    }
}
