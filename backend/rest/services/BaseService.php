<?php
require_once __DIR__ . '/../dao/BaseDao.php';
class BaseService
{
    protected $dao;
    public function __construct($dao)
    {
        $this->dao = $dao;
    }
    // Wrapper to DAO's snake_case methods
    public function getAll()
    {
        return $this->dao->get_all();
    }
    public function getById($id)
    {
        return $this->dao->get_by_id($id);
    }
    public function create($data)
    {
        return $this->dao->add($data);
    }
    public function update($id, $data)
    {
        // BaseDao::update expects (entity, id, id_column = 'id')
        return $this->dao->update($data, $id);
    }
    public function delete($id)
    {
        return $this->dao->delete($id);
    }
}
