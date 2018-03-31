<?php
namespace model\dao;

/**
 * @throws \Exception
 *  1001 正常
 *  9001
 * @author masato
 *
 */
class UserDecideStatusDao extends MySqlDao
{
	protected $_table_name;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_table_name = 'user_decide_status';
	}

	/**
	 *
	 * @param array $arr_profile
	 * @return boolean|Ambigous <boolean, unknown>
	 */
	public function set_regist_undecide(array $arr_request)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array();
		$arr_values['user_id']     = $arr_request['user_id'];
		$arr_values['email']       = $arr_request['email'];
		$arr_values['decide_hash'] = $arr_request['decide_hash'];
		$arr_values['type']        = $arr_request['type'];
		$arr_values['access_date'] = $datetime;
		$arr_values['created_at']  = $datetime;
		$arr_values['updated_at']  = $datetime;

		$query = \DB::insert($this->_table_name);
		$query->set($arr_values);
		list($key, $count) = $query->execute();
		if (empty($count))
		{
			return false;
		}
		return array($key, $count);
	}

	/**
	 * 対象のuser_idのログイン記録をセットします
	 * @param unknown $user_id
	 * @throws \Exception
	 * @return boolean
	 */
	public function update_regist_decided(array $arr_where)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array();
		$arr_values['is_decided']  = '1';
		$arr_values['decide_date'] = $datetime;
		$arr_values['updated_at']  = $datetime;

		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('id', '=', $arr_where['id']);
		$query->where('is_deleted', '=', '0');

		list($key, $count) = $query->execute();
		if (empty($count))
		{
			return false;
		}
		return array($key, $count);
	}

	public function get_undecide(array $arr_where)
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select();
		$query->from($this->_table_name);
		$query->where('is_deleted', '=', '0');
		$query->where('is_decided', '=', '0');
		$query->where('decide_hash', '=', $arr_where['decide_hash']);
		$query->where('type', '=', $arr_where['type']);
		return $query->as_object()->execute()->current();
	}

	public function get_expired_by_user_id($user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select();
		$query->from($this->_table_name);
		$query->where('is_deleted', '=', '0');
		$query->where('is_decided', '=', '0');
		$query->where('access_date', '>', \Date::forge(\Date::forge()->get_timestamp() - (\Config::get('journal.decide_time_limit')))->format('%Y-%m-%d %H:%M:%S'));
		$query->where('user_id', '=', $user_id);
		$result = $query->as_object()->execute()->as_array();
		return $result;
	}
}