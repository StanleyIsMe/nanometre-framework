<?php
namespace App\Model;

use MongoRegex;

/**
 * 會員資料
 *
 * @package App\Model
 */
class MemberMongo extends MongoAbstract
{
    /**
     * @var string table name
     */
    protected $_collection = 'member';

    /**
     * 會員關鍵字模糊搜尋 + memberId
     *
     * @param string $keyWord
     * @param array $ids
     * @return array
     */
    public function useFuzzySearch($keyWord, $ids)
    {
        $where = [
            'memberid' => ['$in' => $ids],
            '$or'      => [
                ['name' => ['$regex' => new MongoRegex("/{$keyWord}/i")]],
                [
                    'email' => ['$regex' => new MongoRegex("/{$keyWord}/i")]
                ],
                ['cellphone' => ['$regex' => new MongoRegex("/{$keyWord}/i")]],
            ]
        ];

        $select = [
            '_id'       => 0,
            'memberid'  => 1,
            'name'      => 1,
            'cellphone' => 1,
            'email'     => 1,
        ];

        $result = $this->table()->find($where, $select);
        return iterator_to_array($result);
    }

    /**
     * 依會員id，取得資料
     *
     * @param array $ids
     * @param array $select
     * @return array
     */
    public function getById(array $ids, array $select = [])
    {
        $where = [
            'memberid' => ['$in' => $ids]
        ];

        if (empty($select)) {
            $result = $this->table()->find($where);
        } else {
            $field['_id'] = 0;
            foreach ($select as $key) {
                $field[$key] = 1;
            }
            $result = $this->table()->find($where, $field);
        }

        return iterator_to_array($result);
    }

    /**
     * 年月模糊搜尋
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function fuzzySearchByYearMonth($year, $month)
    {
        $select = [
            '_id'      => 0,
            'memberid' => 1,
            'gender'   => 1,
            'county'   => 1
        ];
        $where = [
            'register_time' => ['$regex' => new MongoRegex("/{$year}\/{$month}\//")],
        ];
        $result = $this->table()->find($where, $select);
        return iterator_to_array($result);
    }
}