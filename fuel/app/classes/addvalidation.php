<?php

use model\dao\UserDao;
use model\dao\LoginDao;
class AddValidation
{
	/**
	 * API認証キーを確認
	 * @param md5 $api_key
	 * @throws \Exception
	 */
	public static function _validation_check_api_key($api_key)
	{
		\Log::debug('[start]'. __METHOD__);

		$last_initiated_api_key = self::_initiated_api_key(time()-60);
		$just_initiated_api_key = self::_initiated_api_key();
		$next_initiated_api_key = self::_initiated_api_key(time()+60);
		$arr_initiated_api_key = array(
				$just_initiated_api_key,
				$last_initiated_api_key,
				$next_initiated_api_key,
		);
		if ( ! in_array(trim($api_key), $arr_initiated_api_key))
		{
			throw new \Exception('authenticated error', 2001);
		}
	}


	/**
	 * API認証キー許可値を生成
	 * @param string $timestamp
	 * @return string
	 */
	private static function _initiated_api_key($timestamp=null)
	{
		$arr_keys = array(
			0 => 'm',
			1 => 'a',
			2 => 's',
			3 => 'a',
			4 => 't',
			5 => 'k',
			6 => 'i',
			7 => 'm',
			8 => 'u',
			9 => 'r',
		);

		$YmdHi = \Date::forge($timestamp)->format("%Y%m%d%H%M");
		$mid = (int)substr(\Date::forge($timestamp)->format("%M"), -1, 1);
		$initialized_key = md5($arr_keys[$mid] + $YmdHi);

		return $initialized_key;
	}


	/**
	 * ユーザログインハッシュ値のチェック
	 * ここが通ればログイン済となる
	 * @param md5 $login_hash
	 * @return boolean
	 */
	public static function _validation_check_login_hash($login_hash, $user_id, $oauth_type)
	{
		\Log::debug('[start]'. __METHOD__);

		if (empty($login_hash))
		{
			return true;
		}
		if (empty($user_id))
		{
			return true;
		}
		if (empty($oauth_type))
		{
			return true;
		}
		$arr_user_info = array(
				'user_id'    => $user_id,
				'login_hash' => $login_hash,
				'oauth_type' => $oauth_type,
		);
		$login_dao = new LoginDao();
		$login_dao->check_user_login_hash($arr_user_info);

		return true;
	}

	/**
	 * グルーヴオンラインログインでのemailのユニークをチェック
	 * decide=1のみで抽出
	 * @param unknown $email
	 * @throws \Exception
	 * @return boolean 未存在時：true
	 */
	public static function _validation_check_unique_email($email, $oauth_type='email')
	{
		\Log::debug('[start]'. __METHOD__);

		$user_dao = new UserDao();
		$arr_result = $user_dao->is_exist_email($email, $oauth_type);
		if (empty($arr_result))
		{
			return true;
		}
		foreach ($arr_result as $i => $val)
		{
			if ($val->is_decided == '1')
			{
				throw new \Exception('email unique error', 7005);
			}
			if ((strtotime($val->access_date) + \Config::get('journal.decide_time_limit')) >= \Date::forge()->get_timestamp())
			{
				throw new \Exception('email unique error', 7005);
			}
		}
		return true;
	}

	/**
	 * グルーヴオンラインログインでのemailのユニークをチェック
	 * decide=1のみで抽出
	 * 自身のメールアドレスは除く
	 * @param unknown $email
	 * @throws \Exception
	 * @return boolean
	 */
	public static function _validation_check_unique_email_for_edit($email, $oauth_type, $user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$user_dao = new UserDao();
		$arr_result = $user_dao->is_exist_email($email, $oauth_type, $user_id);
		if (empty($arr_result))
		{
			return true;
		}
		foreach ($arr_result as $i => $val)
		{
			if ($val->is_decided == '1')
			{
				throw new \Exception('email unique error', 7005);
			}
			if ((strtotime($val->access_date) + \Config::get('journal.decide_time_limit')) >= \Date::forge()->get_timestamp())
			{
				throw new \Exception('email unique error', 7005);
			}
		}
		return true;
	}
}