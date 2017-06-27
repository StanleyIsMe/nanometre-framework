<?php
namespace App\Model\Database;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';

    public function getAll()
    {
        return $this->all()->toArray();
    }

    public function login($username, $password)
    {
        return $this->where('username', '=', $username)->where('password', '=', $password)->first();
    }
}
