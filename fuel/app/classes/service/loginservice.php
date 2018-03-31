<?php
namespace service;

use Fuel\Core\Presenter;
use model\dao\UserDao;
use model\dao\LoginDao;
use model\dao\model\dao;
/**
 * プライマリーのidは基本使用しない
 * @author masato
 *
 */
class LoginService extends Service
{
	private static $oauth_type;
	private static $oauth_id;
	private static $email;
	private static $password;
	private static $password_org;
	private static $password_digits;
	private static $login_hash;
	private static $user_id;
	private static $user_name;
	private static $member_type;
	private static $last_login;

	public static function set_request()
	{
		\Log::debug('[start]'. __METHOD__);

		foreach (static::$_obj_request as $key => $val)
		{
			if (property_exists('service\LoginService', $key))
			{
				if ($key === 'password' and ! empty($val))
				{
					static::$password        = md5(trim($val));
					static::$password_digits = mb_strwidth(trim($val));
					static::$password_org    = trim($val);
					continue;
				}
				if (is_array($val))
				{
					static::$$key = $val;
				}
				else
				{
					static::$$key = trim($val);
				}
			}
		}
		return true;
	}

	/**
	 * 引数のログイン情報配列をメンバ変数に格納
	 * @param array $arr_user_info
	 * @return boolean
	 */
	public static function set(array $arr_user_info)
	{
		\Log::debug('[start]'. __METHOD__);

		foreach ($arr_user_info as $i => $val)
		{
			if (property_exists('service\LoginService', $i))
			{
				static::$$i = trim($val);
			}
		}
		return true;
	}

	public static function validation_for_login()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);
		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		# oauth_id
		$v = $obj_validate->add('oauth_id', 'oauth_id');
		$v->add_rule('min_length', '3');
		$v->add_rule('max_length', '255');

		# email
		$v = $obj_validate->add('email', 'email');
		$v->add_rule('valid_email');
		if (\Input::post('email') === 'email')
		{
			$v->add_rule('required');
		}

		# password
		$v = $obj_validate->add('password', 'password');
		$v->add_rule('min_length', '4');
		$v->add_rule('max_length', '16');
		$v->add_rule('valid_string', array('numeric', 'alpha'));
		if (\Input::post('email') === 'email')
		{
			$v->add_rule('required');
		}

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	public static function validation_for_logincheck()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);
		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		/* 個別バリデート設定 */
		# oauth_id
		$v = $obj_validate->add('oauth_id', 'oauth_id');
		$v->add_rule('min_length', '3');
		$v->add_rule('max_length', '255');
		if (\Input::post('oauth_type') !== 'email')
		{
			$v->add_rule('required');
		}

		# email
		$v = $obj_validate->add('email', 'email');
		$v->add_rule('valid_email');
		if (\Input::post('email') === 'email')
		{
			$v->add_rule('required');
		}

		# password
		$v = $obj_validate->add('password', 'password');
		$v->add_rule('min_length', '4');
		$v->add_rule('max_length', '16');
		$v->add_rule('valid_string', array('numeric', 'alpha'));
		if (\Input::post('email') === 'email')
		{
			$v->add_rule('required');
		}

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('max_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}


	public static function validation_for_logout()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		/* 個別バリデート設定 */
		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(line)|(facebook)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('max_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	public static function set_transaction_for_login($is_edit=false)
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$user_dao->start_transaction();

			static::set_login();

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			$user_dao->rollback_transaction();
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			if ($e->getCode() == '7010')
			{
				return false;
			}
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * ログイン情報をDBインサート
	 * (既存ユーザ以外は例外処理)
	 * @throws \Exception
	 */
	public static function set_login($is_edit=false)
	{
		\Log::debug('[start]'. __METHOD__);

		switch (static::$oauth_type)
		{
			case \Config::get('login.oauth_type.email'):
				$strategy = new ConcreateStrategyEmail();
				break;
			case \Config::get('login.oauth_type.facebook'):
				$strategy = new ConcreateStrategyFacebook();
				break;
			case \Config::get('login.oauth_type.line'):
				$strategy = new ConcreateStrategyLine();
				break;
			case \Config::get('login.oauth_type.twitter'):
				$strategy = new ConcreateStrategyTwitter();
				break;
			case \Config::get('login.oauth_type.yahoo'):
				$strategy = new ConcreateStrategyYahoo();
				break;
			case \Config::get('login.oauth_type.google'):
				$strategy = new ConcreateStrategyGoogle();
				break;
			default:
				throw new \Exception('required error[oauth_type]', 7002);
		}
		$obj_login_context = new LoginContext($strategy);
		# トランザクション開始
		$user_dao = new UserDao();

		# loginテーブルにログイン情報をインサート
		$arr_params = array(
			'oauth_type' => static::$oauth_type,
			'oauth_id'   => static::$oauth_id,
			'user_id'    => static::$user_id,
			'email'      => static::$email,
			'password'   => static::$password,
			'remark'     => ($is_edit)? 'edit': 'login',
		);
		$arr_user_info = $obj_login_context->login($arr_params);
		# ユーザ未登録の場合
		if (empty($arr_user_info))
		{
			throw new \Exception('ログインユーザ情報が取得できません', 7010);
		}
		# edit時はユーザテーブルにラストログイン時をセットしない
		if ( ! $is_edit)
		{
			# ユーザテーブルにラストログインをセット
			$user_dao->update_last_login($arr_user_info['user_id']);
		}

		static::$login_hash      = $arr_user_info['login_hash'];
		static::$user_id         = $arr_user_info['user_id'];
		static::$oauth_id        = isset($arr_user_info['oauth_id'])? $arr_user_info['oauth_id']: '';
		static::$oauth_type      = isset($arr_user_info['oauth_type'])? $arr_user_info['oauth_type']: 'email';
		static::$user_name       = isset($arr_user_info['user_name'])? $arr_user_info['user_name']: '';
		static::$email           = $arr_user_info['email'];
		static::$password_digits = $arr_user_info['password_digits'];
		static::$member_type     = $arr_user_info['member_type'];
		return true;
	}

	/**
	 * ログアウト処理を行う
	 */
	public static function set_logout()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$login_dao = new LoginDao();
			$user_dao  = new UserDao();

			$login_dao->start_transaction();
			$logout = $login_dao->set_logout(static::$user_id, static::$login_hash);
			if (empty($logout))
			{
				throw new \Exception('can not logout', 7011);
			}
			$user_dao->update_last_logout(static::$user_id);
			$login_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			$login_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * プロパティからユーザ情報を配列で取得
	 * @return multitype:NULL
	 */
	public static function get_user_info_from_property()
	{
		\Log::debug('[start]'. __METHOD__);

		return array(
			'oauth_type'      => static::$oauth_type,
			'oauth_id'        => static::$oauth_id,
			'user_id'         => static::$user_id,
			'user_name'       => static::$user_name,
			'email'           => static::$email,
			'password'        => static::$password,
			'password_digits' => static::$password_digits,
			'login_hash'      => static::$login_hash,
			'member_type'     => static::$member_type,
			'last_login'      => static::$last_login,
		);
	}

	public static function generate_login_hash()
	{
		$rand = rand(1, 399);
		$hash = md5(\Date::forge()->format('%Y%m%d%H%M%S'). $rand);
		return $hash;
	}

	/**
	 * # ログイン済みチェック
	 *
	 * @return boolean
	 */
	public static function check_user_login_hash()
	{
		\Log::debug('[start]'. __METHOD__);

		$login_dao = new LoginDao();

		return $login_dao->check_user_login_hash(array(
			'user_id'    => static::$user_id,
			'login_hash' => static::$login_hash,
			'oauth_type' => static::$oauth_type,
		));
	}
}