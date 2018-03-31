<?php
namespace model\dao;

use Fuel\Core\Database_Connection;
use Fuel\Core\Model;

class MySqlDao extends Model
{
	protected $_table_name = null;

	public function __construct($_table_name=null)
	{
		$this->_table_name = $_table_name;
	}

	public function start_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$db = Database_Connection::instance();
		if ( ! $db->in_transaction())
		{
			return $db->start_transaction();
		}
	}

	public function commit_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$db = Database_Connection::instance();
		if ($db->in_transaction())
		{
			return $db->commit_transaction();
		}
	}

	public function rollback_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$db = Database_Connection::instance();
		if ($db->in_transaction())
		{
			return $db->rollback_transaction();
		}
	}

	public function in_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$db = Database_Connection::instance();
		return $db->in_transaction();
	}

	public function get(array $arr_where=array(), array $arr_columns=array())
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select_array($arr_columns);
		$query->from($this->_table_name);
		$query->where('is_deleted', '=', '0');
		foreach ($arr_where as $key => $val)
		{
			if (preg_match('/([<>]+[=]*)$/', trim($key), $match))
			{
				$key = preg_replace('/([<>]+[=]*)$/', '', trim($key));
				$query->where($key, $match[1], $val);
			}
			else
			{
				$query->where($key, '=', $val);
			}
		}
		return $query->as_object()->execute()->as_array();
	}

}