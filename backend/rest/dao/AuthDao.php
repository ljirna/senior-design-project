<?php
require_once __DIR__ . '/BaseDao.php';
class AuthDao extends BaseDao
{
    protected $users;

    public function __construct()
    {
        $this->users = "users"; 
        parent::__construct($this->users);
    }

    public function get_user_by_email($email)
    {
        $query = "SELECT * FROM " . $this->users . " WHERE email = :email";
        return $this->query_unique($query, ['email' => $email]);
    }
}
