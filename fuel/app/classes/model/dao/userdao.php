<?php
namespace model\dao;

/**
 * @throws \Exception
 *  1001 正常
 *  9001
 * @author masato
 *
 */
class UserDao extends MySqlDao
{
	protected $_table_name;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_table_name = 'user';
	}

	/**
	 *
	 * @param array $arr_profile
	 * @return boolean|Ambigous <boolean, unknown>
	 */
	public function set_profile(array $arr_request)
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_values = array();
		foreach ($arr_request as $i => $val)
		{
			if ($val === "")
			{
				continue;
			}
			$arr_values[$i] = $val;
		}
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
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
	public function update_last_login($user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array(
				'last_login'  => $datetime,
				'updated_at'  => $datetime,
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('id', '=', $user_id);
		$result = $query->execute();
		if ($result === false)
		{
			throw new \Exception('該当するユーザは存在しません', 9001);
		}
		return true;
	}


	/**
	 * 対象のuser_idのログアウト記録をセットします
	 * @param unknown $user_id
	 * @throws \Exception
	 * @return boolean
	 */
	public function update_last_logout($user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_values = array(
				'last_logout' => $datetime,
				'updated_at'  => $datetime,
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('id', '=', $user_id);
		$result = $query->execute();
		if ($result === false)
		{
			throw new \Exception('該当するユーザは存在しません', 9001);
		}
		return true;
	}

	/**
	 * ユーザ情報を更新する
	 * @param array $arr_profile
	 * @param array $arr_where
	 * @return Ambigous <boolean, unknown>
	 */
	public function update_profile(array $arr_profile, array $arr_where)
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::update($this->_table_name);
		if (isset($arr_profile['user_name']))
		{
			$query->value('user_name', $arr_profile['user_name']);
		}
		if (isset($arr_profile['email']))
		{
			$query->value('email', $arr_profile['email']);
		}
		if (isset($arr_profile['password']))
		{
			$query->value('password', $arr_profile['password']);
		}
		if (isset($arr_profile['is_decided']))
		{
			$query->value('is_decided', $arr_profile['is_decided']);
		}
		if (isset($arr_profile['decide_date']))
		{
			$query->value('decide_date', $arr_profile['decide_date']);
		}
		if (isset($arr_profile['last_login']))
		{
			$query->value('last_login', $arr_profile['last_login']);
		}
		$query->where('is_deleted', '=', '0');
		$query->where('id', '=', $arr_where['user_id']);
		return $query->execute();
	}

	public function get_userinfo_by_user_id($user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select();
		$query->from($this->_table_name);
		$query->where('is_deleted', '=', '0');
		$query->where('id', '=', $user_id);
		return $query->as_object()->execute()->current();
	}

	/**
	 * @param array $arr_user_info[email, md5(password), oauth_type, user_id(任意)]
	 * @return boolean|unknown
	 */
	public function get_userinfo_by_email_and_password(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		$email      = $arr_user_info['email'];
		$password   = $arr_user_info['password'];
		$oauth_type = isset($arr_user_info['oauth_type'])? $arr_user_info['oauth_type']: '';
		$user_id    = isset($arr_user_info['user_id'])? $arr_user_info['user_id']: '';
		if (empty($email)) return false;
		if (empty($password)) return false;

		$arr_columns = array(
				array('u.id', 'user_id'),
				array('u.user_name', 'user_name'),
				array('u.oauth_type', 'oauth_type'),
				array('u.oauth_id', 'oauth_id'),
				array('u.member_type', 'member_type'),
				array('u.email', 'email'),
				array('u.password_digits', 'password_digits'),
				array('u.last_login', 'last_login'),
				array('l.id', 'login_id'),
				array('l.login_hash', 'login_hash'),
		);
		$query = \DB::select_array($arr_columns);
		$query->from(array($this->_table_name, 'u'));
		$query->join(array('login', 'l'), 'left');
		$query->on('u.id', '=', 'l.user_id');
		$query->on('l.is_deleted', '=', \DB::expr('0'));

		if ( ! empty($user_id))
		{
			$query->where('u.id', '=', $user_id);
		}
		$query->where('u.email',      '=', $email);
		if ( ! empty($oauth_type))
		{
			$query->where('u.oauth_type', '=', $oauth_type);
		}
		$query->where('u.password',   '=', $password);
		$query->where('u.is_deleted', '=', '0');
		$query->where('u.is_decided', '=', '1');
		$query->order_by('u.id', 'DESC');
		$query->order_by('l.id', 'DESC');
		$obj_result = $query->as_object()->execute()->as_array();
		if (empty($obj_result))
		{
			return false;
		}
		return $obj_result;
	}

	/**
	 * @param array $arr_user_info[email, md5(password), oauth_type, user_id(任意)]
	 * @return boolean|unknown
	 */
	public function get_userinfo_by_email_and_decide_before(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		$email      = $arr_user_info['email'];
		$oauth_type = $arr_user_info['oauth_type'];
		$user_id    = isset($arr_user_info['user_id'])? $arr_user_info['user_id']: '';
		if (empty($email)) return false;

		$arr_columns = array(
				array('u.id', 'user_id'),
				array('u.user_name', 'user_name'),
				array('u.oauth_type', 'oauth_type'),
				array('u.oauth_id', 'oauth_id'),
				array('u.member_type', 'member_type'),
				array('u.email', 'email'),
				array('u.password_digits', 'password_digits'),
				array('u.last_login', 'last_login'),
				array('u.is_decided', 'is_decided'),
				array('l.id', 'login_id'),
				array('l.login_hash', 'login_hash'),
				array('d.decide_hash', 'decide_hash'),
				array('d.access_date', 'access_date'),
		);
		$query = \DB::select_array($arr_columns);
		$query->from(array($this->_table_name, 'u'));
		$query->join(array('login', 'l'), 'left');
		$query->on('u.id', '=', 'l.user_id');
		$query->on('l.is_deleted', '=', \DB::expr('0'));
		$query->join(array('user_decide_status', 'd'));
		$query->on('u.id', '=', 'd.user_id');
		$query->on('d.is_decided', '=', \DB::expr('0'));
		$query->on('d.is_deleted', '=', \DB::expr('0'));

		if ( ! empty($user_id))
		{
			$query->where('u.id', '=', $user_id);
		}
		$query->where('u.email',      '=', $email);
		$query->where('u.oauth_type', '=', $oauth_type);
		$query->where('u.is_deleted', '=', '0');
		$query->and_where_open();
		$query->or_where('u.is_decided', '=', '1');
		$query->or_where_open();
		$query->where('u.is_decided', '=', '0');
		$query->where('d.access_date', '>', \Date::forge(\Date::forge()->get_timestamp() - (\Config::get('journal.decide_time_limit')))->format('%Y-%m-%d %H:%M:%S'));
		$query->or_where_close();
		$query->and_where_close();
		$query->order_by('u.id', 'DESC');
		$query->order_by('l.id', 'DESC');
		$obj_result = $query->as_object()->execute()->as_array();
		if (empty($obj_result))
		{
			return false;
		}
		return $obj_result;
	}

	/**
	 * @param array $arr_user_info[email, md5(password), oauth_type, user_id(任意)]
	 * @return boolean|unknown
	 */
	public function get_userinfo_by_oauth_and_decide_before(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		$oauth_type = $arr_user_info['oauth_type'];
		$user_id    = isset($arr_user_info['user_id'])? $arr_user_info['user_id']: '';
		if (empty($arr_user_info['oauth_id'])) return false;

		$arr_columns = array(
				array('u.id', 'user_id'),
				array('u.user_name', 'user_name'),
				array('u.oauth_type', 'oauth_type'),
				array('u.oauth_id', 'oauth_id'),
				array('u.member_type', 'member_type'),
				array('u.email', 'email'),
				array('u.password_digits', 'password_digits'),
				array('u.last_login', 'last_login'),
				array('u.is_decided', 'is_decided'),
				array('l.id', 'login_id'),
				array('l.login_hash', 'login_hash'),
				array('d.decide_hash', 'decide_hash'),
				array('d.access_date', 'access_date'),
		);
		$query = \DB::select_array($arr_columns);
		$query->from(array($this->_table_name, 'u'));
		$query->join(array('login', 'l'), 'left');
		$query->on('u.id', '=', 'l.user_id');
		$query->on('l.is_deleted', '=', \DB::expr('0'));
		$query->join(array('user_decide_status', 'd'));
		$query->on('u.id', '=', 'd.user_id');
		$query->on('d.is_decided', '=', \DB::expr('0'));
		$query->on('d.is_deleted', '=', \DB::expr('0'));

		if ( ! empty($user_id))
		{
			$query->where('u.id', '=', $user_id);
		}
		$query->where('u.oauth_id',   '=', $arr_user_info['oauth_id']);
		$query->where('u.oauth_type', '=', $oauth_type);
		$query->where('u.is_deleted', '=', '0');
		$query->and_where_open();
		$query->or_where('u.is_decided', '=', '1');
		$query->or_where_open();
		$query->where('u.is_decided', '=', '0');
		$query->where('d.access_date', '>', \Date::forge(\Date::forge()->get_timestamp() - (\Config::get('journal.decide_time_limit')))->format('%Y-%m-%d %H:%M:%S'));
		$query->or_where_close();
		$query->and_where_close();
		$query->order_by('u.id', 'DESC');
		$query->order_by('l.id', 'DESC');
		$obj_result = $query->as_object()->execute()->as_array();
		if (empty($obj_result))
		{
			return false;
		}
		return $obj_result;
	}

	/**
	 * @param array $arr_user_info[email, md5(password), oauth_type, user_id(任意)]
	 * @return boolean|unknown
	 */
	public function get_userinfo_by_email($email, $oauth_type)
	{
		\Log::debug('[start]'. __METHOD__);

		if (empty($email)) return false;
		if (empty($oauth_type)) return false;

		$query = \DB::select();
		$query->from($this->_table_name);
		$query->where('email',      '=', $email);
		$query->where('oauth_type', '=', $oauth_type);
		$query->where('is_decided', '=', '1');
		$query->where('is_deleted', '=', '0');
		$obj_result = $query->execute()->current();
		if (empty($obj_result))
		{
			return false;
		}
		return $obj_result;
	}

	/**
	 *
	 * @param array $arr_user_info[oauth_type, oauth_id, user_id(任意)]
	 * @return boolean|unknown
	 */
	public function get_userinfo_by_oauth(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		$oauth_type = $arr_user_info['oauth_type'];
		$oauth_id   = $arr_user_info['oauth_id'];
		if (empty($oauth_type)) return false;
		if (empty($oauth_id)) return false;
		$user_id = isset($arr_user_info['user_id'])? $arr_user_info['user_id']: '';

		$arr_columns = array(
				array('u.id', 'user_id'),
				array('u.user_name', 'user_name'),
				array('u.oauth_type', 'oauth_type'),
				array('u.oauth_id', 'oauth_id'),
				array('u.member_type', 'member_type'),
				array('u.email', 'email'),
				array('u.password_digits', 'password_digits'),
				array('u.last_login', 'last_login'),
				array('l.id', 'login_id'),
				array('l.login_hash', 'login_hash'),
		);
		$query = \DB::select_array($arr_columns);
		$query->from(array($this->_table_name, 'u'));
		$query->join(array('login', 'l'), 'left');
		$query->on('u.id', '=', 'l.user_id');
		$query->on('l.is_deleted', '=', \DB::expr('0'));

		if ( ! empty($user_id))
		{
			$query->where('u.id', '=', $user_id);
		}
		$query->where('u.oauth_id',   '=', $oauth_id);
		$query->where('u.oauth_type', '=', $oauth_type);
		$query->where('u.is_deleted', '=', '0');
		$query->where('u.is_decided', '=', '1');
		$query->order_by('u.id', 'DESC');
		$query->order_by('l.id', 'DESC');
		$obj_result = $query->as_object()->execute()->current();

		if (empty($obj_result))
		{
			return false;
		}
		return $obj_result;
	}

	/**
	 * emailがuserテーブルに存在することの確認
	 * @param isset_dto boolean true:dtoに結果が代入される
	 * @return boolean 存在時: true, 未存在時：false
	 */
	public function is_exist_email($email, $oauth_type, $ignore_user_id=null)
	{
		\Log::debug('[start]'. __METHOD__);

		if (empty($email)) return false;
		if (empty($oauth_type)) return false;

		$query = \DB::select();
		$query->from(array($this->_table_name, 'u'));
		$query->where('u.is_deleted', '=', '0');
		$query->where('u.is_decided', '=', '1');
		$query->where('u.email',      '=', $email);
		$query->where('u.oauth_type', '=', $oauth_type);
		if (! empty($ignore_user_id))
		{
			$query->where('u.id', '!=', $ignore_user_id);
		}
		$arr_result = $query->as_object()->execute()->as_array();

		return $arr_result;
	}

	public function get_users()
	{
		\Log::debug('[start]'. __METHOD__);

		$query = \DB::select('u.id');
		$query->from(array($this->_table_name, 'u'));
		$query->join(array('payment', 'p'));
		$query->on('p.user_id', '=', 'u.id');
		$query->where('u.is_deleted', '=', '0');
		$query->where('p.is_deleted', '=', '0');
		$query->group_by('u.id');
		$result = $query->as_object()->execute()->as_array();
		return $result;
	}

}