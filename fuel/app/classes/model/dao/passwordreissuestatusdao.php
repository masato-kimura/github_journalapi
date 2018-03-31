<?php
namespace model\dao;

/**
 * @throws \Exception
 *  1001 正常
 *  9001
 * @author masato
 *
 */
class PasswordReissueStatusDao extends MySqlDao
{
	protected $_table_name;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_table_name = 'password_reissue_status';
	}

	/**
	 *
	 * @param array $arr_profile
	 * @return boolean|Ambigous <boolean, unknown>
	 */
	public function set_password_reissue_hash(array $arr_request)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array();
		$arr_values['user_id']      = $arr_request['user_id'];
		$arr_values['email']        = $arr_request['email'];
		$arr_values['reissue_hash'] = $arr_request['reissue_hash'];
		$arr_values['access_date']  = $datetime;
		$arr_values['created_at']   = $datetime;
		$arr_values['updated_at']   = $datetime;

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
	public function update_reissue_decided($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array();
		$arr_values['is_reissue']  = '1';
		$arr_values['reissue_date'] = $datetime;
		$arr_values['updated_at']   = $datetime;

		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('id', '=', $id);
		$query->where('is_deleted', '=', '0');

		list($key, $count) = $query->execute();
		if (empty($count))
		{
			return false;
		}
		return array($key, $count);
	}

	public function get_unreissue($reissue_hash, $oauth_type)
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select();
		$query->from(array($this->_table_name, 'p'));
		$query->join(array('user', 'u'));
		$query->on('p.user_id', '=', 'u.id');
		$query->where('p.is_deleted', '=', '0');
		$query->where('u.is_deleted', '=', '0');
		$query->where('u.is_decided', '=', '1');
		$query->where('p.is_reissue', '=', '0');
		$query->where('p.reissue_hash', '=', $reissue_hash);
		$query->where('u.oauth_type', '=', $oauth_type);
		return $query->execute()->current();
	}

	public function get_valid_reissue_data(array $arr_where, $expired_time=180)
	{
		\Log::debug('[start]'. __METHOD__);

		$timestamp = \Date::forge()->get_timestamp() - $expired_time;

		$query = \DB::select();
		$query->from($this->_table_name);
		$query->where('is_deleted', '=', '0');
		$query->where('user_id', '=', $arr_where['user_id']);
		$query->where('email', '=', $arr_where['email']);
		$query->where('is_reissue', '=', '0');
		$query->where('access_date', '>=', \Date::forge($timestamp)->format('%Y-%m-%d %H:%M:%S'));
		return $query->execute()->as_array();
	}


}