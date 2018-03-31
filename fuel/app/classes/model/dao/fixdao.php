<?php
namespace model\dao;

class FixDao extends MySqlDao
{
	public function __construct()
	{
		$this->_table_name = "fix";
	}


	public function get_list($user_id, $offset=0, $limit=100)
	{
		try
		{
			$query = \DB::select_array();
			$query->from(array($this->_table_name, 'f'));
			$query->where('f.is_deleted', '=', '0');
			$query->where('f.user_id', '=', $user_id);
			//$query->where('f.is_disp', '=', '1');
			$query->offset($offset);
			$query->limit($limit);
			$query->order_by('f.sort', 'ASC');
			return $query->execute()->as_array();
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}

	public function set_sorted($sort, array $arr_where)
	{
		$query = \DB::update($this->_table_name);
		$query->value('sort', $sort);
		$query->value('updated_at', \Date::forge()->format('%Y-%m-%d %H:%M:%S'));
		$query->where('id', '=', $arr_where['id']);
		$query->where('user_id', '=', $arr_where['user_id']);
		$query->where('is_deleted', '=', '0');
		return $query->execute();
	}

	public function add_data(array $arr_value)
	{
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_insert_value = array(
				'user_id' => $arr_value['user_id'],
				'name'    => $arr_value['name'],
				'remark'  => $arr_value['remark'],
				'is_fix'  => $arr_value['is_fix'],
				'is_disp' => $arr_value['is_disp'],
				'to_aggre'=> $arr_value['to_aggre'],
				'sort'    => $arr_value['sort'],
				'created_at' => $datetime,
				'updated_at' => $datetime,
		);
		$query = \DB::insert($this->_table_name);
		$query->set($arr_insert_value);
		return $query->execute();
	}

	public function add_multi_data(array $arr_multi_data)
	{
		$query = \DB::insert($this->_table_name);
		$query->columns(array_keys($arr_multi_data[0]));
		foreach ($arr_multi_data as $i => $val)
		{
			$query->values($val);
		}
		return $query->execute();
	}

	public function edit_data(array $arr_value, array $arr_where)
	{
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_insert_value = array(
				'name'       => $arr_value['name'],
				'remark'     => $arr_value['remark'],
				'is_fix'     => $arr_value['is_fix'],
				'is_disp'    => $arr_value['is_disp'],
				'to_aggre'   => $arr_value['to_aggre'],
				'updated_at' => $datetime,
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_insert_value);
		$query->where('is_deleted', '=', '0');
		$query->where('id', '=', $arr_where['id']);
		$query->where('user_id', '=', $arr_where['user_id']);
		return $query->execute();
	}

	public function remove_data(array $arr_value, array $arr_where)
	{
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_insert_value = array(
				'is_deleted' => '1',
				'updated_at' => $datetime,
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_insert_value);
		$query->where('is_deleted', '=', '0');
		$query->where('id', '=', $arr_where['id']);
		$query->where('user_id', '=', $arr_where['user_id']);
		return $query->execute();
	}
}
