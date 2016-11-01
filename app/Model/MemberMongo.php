<?php
namespace App\Model;

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
     * 依id，取得資料
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
}