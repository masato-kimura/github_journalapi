<?php
namespace model\dao;

/**
 * @throws \Exception
 *  1001 正常
 *  8001 システムエラー
 *  8002 DBエラー
 *  9001
 *
 * @author masato
 *
 */
class LoginDao extends MySqlDao
{
	protected $_table_name;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_table_name = 'login';
	}


	/**
	 *
	 * @param array $arr_profile
	 * @return boolean|Ambigous <boolean, unknown>
	 */
	public function set_profile(array $arr_profile)
	{
		\Log::debug('[start]'. __METHOD__);

		$result = $this->save($arr_profile);
		if (empty($result))
		{
			throw new \Exception('no return db_request', 8002);
		}
		return $result;
	}


	/**
	 *
	 * @param array login_hash, user_id,
	 * @throws \GolException
	 * @throws \Exception
	 * @return boolean
	 */
	public function set_login(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		if (empty($arr_user_info['login_hash']))
		{
			throw new \GolException('リクエストにlogin_hashが設定されてません');
		}

		if (empty($arr_user_info['user_id']))
		{
			return false;
		}

		$remark = (empty($arr_user_info['remark']))? 'login': $arr_user_info['remark'];
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_params = array(
			'user_id'    => $arr_user_info['user_id'],
			'login_hash' => $arr_user_info['login_hash'],
			'remark'     => $remark,
			'created_at' => $datetime,
			'updated_at' => $datetime,
		);

		$query = \DB::insert($this->_table_name);
		$query->set($arr_params);
		$result = $query->execute();
		if (empty($result))
		{
			throw new \Exception('ログイン情報を登録できません', 8002); // DBエラー
		}
		return $result;
	}

	/**
	 *
	 * @param array login_hash, user_id,
	 * @throws \GolException
	 * @throws \Exception
	 * @return boolean
	 */
	public function update_login(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		if (empty($arr_user_info['login_id']))
		{
			throw new \Exception('リクエストにlogin_idが設定されてません');
		}

		$remark = empty($arr_user_info['remark'])? 'login': $arr_user_info['remark'];
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$arr_params = array(
				'updated_at' => $datetime,
		);

		$query = \DB::update($this->_table_name);
		$query->set($arr_params);
		$query->where('id', '=', $arr_user_info['login_id']);
		$result = $query->execute();
		if (empty($result))
		{
			throw new \Exception('ログイン情報を更新できません', 8002); // DBエラー
		}
		return $result;
	}



	/**
	 * ログアウト処理を行う
	 * @return int 更新件数
	 */
	public function set_logout($user_id, $login_hash)
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_values = array(
				'is_deleted' => 1,
				'remark'     => 'logout',
				'updated_at' => \Date::forge()->format('%Y-%m-%d %H:%M:%S'),
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('user_id', '=', $user_id);
		$query->where('login_hash', '=', $login_hash);
		$result = $query->execute();

		return $result;
	}


	public function remove_login_hash_for_reflash($user_id, $login_hash='')
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_values = array(
				'is_deleted' => 1,
				'remark'     => 'reflesh',
				'updated_at' => \Date::forge()->format('%Y-%m-%d %H:%M:%S'),
		);
		$query = \DB::update($this->_table_name);
		$query->set($arr_values);
		$query->where('is_deleted', '=', '0');
		$query->where('user_id',    '=', $user_id);
		if ( ! empty($login_hash))
		{
			$query->where('login_hash', '=', $login_hash);
		}
		return $query->execute();
	}


	public function set_login_hash_for_reflash($login_hash)
	{
		$login_dto = LoginDto::get_instance();
		$arr_params = array(
			'user_id' => $login_dto->get_user_id(),
			'login_hash' => $login_hash,
			'remark' => 'reflesh',
		);

		return $this->save($arr_params);
	}


	/**
	 * ユーザIDとログインハッシュが有効であることの確認
	 * @throws \Exception
	 * @return boolean
	 */
	public function check_user_login_hash(array $arr_user_info)
	{
		$arr_where = array(
			'user_id'    => $arr_user_info['user_id'],
			'login_hash' => $arr_user_info['login_hash'],
			'oauth_type' => $arr_user_info['oauth_type'],
		);

		$query = \DB::select('l.id');
		$query->from(array('login', 'l'));
		$query->join(array('user', 'u'));
		$query->on('l.user_id', '=', 'u.id');
		$query->where('l.is_deleted', '=', '0');
		$query->where('u.is_deleted', '=', '0');
		$query->where('l.user_id', '=', $arr_user_info['user_id']);
		$query->where('l.login_hash', '=', $arr_user_info['login_hash']);
		$query->where('u.oauth_type', '=', $arr_user_info['oauth_type']);
		$query->where('u.is_decided', '=', '1');

		$result = $query->as_object()->execute()->as_array();
		if (empty($result))
		{
			throw new \Exception('login hash error[unknown]'. $arr_user_info['login_hash'], 7010);
		}
		if (count($result) > 1)
		{
			throw new \Exception('login hash error[duplicate]'. $arr_user_info['login_hash'], 7010);
		}
		return true;
	}
}