<?php

namespace Core\Interfaces;

interface DatabaseInterface
{
    public function query($query, $params = []);
    public function fetchAll($result = null);
    public function fetchAssoc($result = null);
    public function fetchRow($result = null);
    public function affectedRows();
    public function lastInsertId();
    public function escape($string);
    public function prepare($query);
    public function executePrepared($stmt, $params = []);
    public function beginTransaction();
    public function commit();
    public function rollback();
    public function inTransaction();
    public function getError();
    public function getErrorCode();
    public function insert($table, $data);
    public function update($table, $data, $where, $whereParams = []);
    public function delete($table, $where, $whereParams = []);
    public function count($table, $where = '1=1', $whereParams = []);
    public function rpc($function, $params = []);
    public function getConnection();
    public function close();
    public function isConnected();
    public function reconnect();
    public function getDatabaseInfo();
}