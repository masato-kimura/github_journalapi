<?php
namespace service;

use model\dao\UserDao;
use model\dao\LoginDao;
class ConcreateStrategyLine implements LoginStrategy
{
	/**
	 * @param $arr_user_info[oauth_type, oauth_id, user_id(任意)]
	 * @see \service\LoginStrategy::get_user_info()
	 */
	public function get_user_info(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		$dao = new UserDao();
		if ($arr_user_info['oauth_type'] == 'line' and ! empty($arr_user_info['oauth_id']))
		{
			return array($dao->get_userinfo_by_oauth($arr_user_info));
		}
		else
		{
			$obj_result = $dao->get_userinfo_by_email_and_password($arr_user_info);
			if (empty($obj_result))
			{
				return array();
			}
			return $obj_result;
		}
	}

	/**
	 * @param $arr_user_info[oauth_type, oauth_id, user_id(任意)]
	 * @see \service\LoginStrategy::login()
	 */
	public function login(array $arr_user_info_params)
	{
		\Log::debug('[start]'. __METHOD__);

		// user情報をDBから取得
		$arr_user_info_from_db = $this->get_user_info($arr_user_info_params);
		if (empty($arr_user_info_from_db))
		{
			return array();
		}
		$arr_user_info = (array)current($arr_user_info_from_db);
		$arr_user_info['remark'] = (empty($arr_user_info_params['remark'])? 'login': $arr_user_info_params['remark']);

		// login_hash値を取得
		if (empty($arr_user_info['login_hash']))
		{
			$arr_user_info['login_hash'] = LoginService::generate_login_hash();
		}

		// データベースにログイン情報を登録
		$login_dao = new LoginDao();
		if (empty($arr_user_info['login_id']))
		{
			$login_dao->set_login($arr_user_info);
		}
		else
		{
			$login_dao->update_login($arr_user_info);
		}

		return array(
			'login_hash'      => $arr_user_info['login_hash'],
			'user_id'         => $arr_user_info['user_id'],
			'user_name'       => ! empty($arr_user_info['user_name'])? $arr_user_info['user_name']: '',
			'oauth_id'        => $arr_user_info['oauth_id'],
			'oauth_type'      => $arr_user_info['oauth_type'],
			'email'           => $arr_user_info['email'],
			'member_type'     => $arr_user_info['member_type'],
			'password_digits' => $arr_user_info['password_digits'],
			'last_login'      => $arr_user_info['last_login'],
		);
	}

	public function logout()
	{

	}
}