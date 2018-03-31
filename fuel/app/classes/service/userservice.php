<?php
namespace service;

use model\dao\UserDao;
use model\dao\LoginDao;
use model\dao\UserDecideStatusDao;
use model\dao\PasswordReissueStatusDao;
use Email\Email;
use model\dao\model\dao;
class UserService extends Service
{
	private static $user_id;
	private static $user_name;
	private static $first_name;
	private static $last_name;
	private static $date;
	private static $password;
	private static $password_digits = 0;
	private static $password_org;
	private static $email;
	private static $link;
	private static $gender;
	private static $locale;
	private static $birthday;
	private static $birthday_year;
	private static $birthday_month;
	private static $birthday_day;
	private static $birthday_secret = 0;
	private static $old;
	private static $old_secret = 0;
	private static $local;
	private static $country;
	private static $postal_code;
	private static $pref;
	private static $locality;
	private static $street;
	private static $profile_fields;
	private static $facebook_url;
	private static $google_url;
	private static $twitter_url;
	private static $oauth_type;
	private static $oauth_id;
	private static $picture_url;
	private static $is_leaved = 0;
	private static $leave_date;
	private static $last_login;
	private static $last_logout;
	private static $member_type = 0;
	private static $login_hash;
	private static $is_decided;
	private static $decide_date;
	private static $decide_hash;
	private static $user_id_before;
	private static $user_name_before;
	private static $password_before;
	private static $email_before;
	private static $reissue_hash;
	private static $is_username_change = false;
	private static $is_password_change = false;
	private static $is_email_change = false;

	public static function set_request()
	{
		\Log::debug('[start]'. __METHOD__);

		foreach (static::$_obj_request as $key => $val)
		{
			if (property_exists('service\UserService', $key))
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
			static::$user_name = static::generate_user_name();
			static::$birthday  = static::generate_birthday();
			static::$old       = static::generate_old();
		}
		return true;
	}
	private static function generate_user_name()
	{
		$user_name = static::$user_name;
		if (empty($user_name))
		{
			if ( ! empty(static::$last_name) and ! empty(static::$first_name))
			{
				return static::$last_name. ' '. static::$first_name;
			}
		}
		return $user_name;
	}
	private static function generate_birthday()
	{
		$birthday_year  = static::$birthday_year;
		$birthday_month = static::$birthday_month;
		$birthday_day   = static::$birthday_day;
		if ( ! empty($birthday_year) and  ! empty($birthday_month) and  ! empty($birthday_day))
		{
			return $birthday_year. '-'. $birthday_month. '-'. $birthday_day;
		}
		return '';
	}
	private static function generate_old()
	{
		$old = static::$old;
		$birthday = static::$birthday;
		if (empty($old) and  ! empty($birthday))
		{
			return (int)((Date::forge()->format('%Y%m%d') - preg_replace('/[-\/]/i', '', $birthday())) / 10000);
		}
		return $old;
	}


	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_regist()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# auth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

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
		$v->add_rule('required');
		$v->add_rule('check_unique_email', static::$_obj_request->oauth_type); // 独自, to exception

		# password
		$v = $obj_validate->add('password', 'password');
		$v->add_rule('max_length', '16');
		$v->add_rule('min_length', '4');
		$v->add_rule('valid_string', array('numeric', 'alpha'));
		$v->add_rule('required');

		# user_name
		$v = $obj_validate->add('user_name', 'ユーザ名');
		$v->add_rule('max_length', '100');
		$v->add_rule('required');

		# first_name
		$v = $obj_validate->add('first_name', '名');
		$v->add_rule('max_length', '50');

		# last_name
		$v = $obj_validate->add('last_name', '苗字');
		$v->add_rule('max_length', '50');

		# date
		$v = $obj_validate->add('date', '登録日');
		$v->add_rule('max_length', '10');

		# link
		$v = $obj_validate->add('link', 'リンク');
		$v->add_rule('max_length', '255');
		$v->add_rule('valid_url');

		# gender
		$v = $obj_validate->add('gender', '性別');
		$v->add_rule('max_length', '10');

		# birthday
		$v = $obj_validate->add('birthday', '誕生年月日');
		$v->add_rule('max_length', '100');

		# birthday_year
		$v = $obj_validate->add('birthday_year', '誕生年');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '4');

		# birthday_month
		$v = $obj_validate->add('birthday_month', '誕生月');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '2');
		$v->add_rule('numeric_min', 0);
		$v->add_rule('numeric_max', 12);

		# birthday_day
		$v = $obj_validate->add('birthday_day', '誕生日');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '2');
		$v->add_rule('numeric_min', 0);
		$v->add_rule('numeric_max', 31);

		# birthday_secret
		$v = $obj_validate->add('birthday_secret', '誕生日表示フラグ');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('exact_length', 1);

		# old
		$v = $obj_validate->add('old', '年齢');
		$v->add_rule('valid_string', array('numeric'));

		# old_secret
		$v = $obj_validate->add('old_secret', '年齢表示フラグ');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('exact_length', 1);

		# locale
		$v = $obj_validate->add('locale', 'ロケール');
		$v->add_rule('max_length', '100');

		# country
		$v = $obj_validate->add('country', '国名');
		$v->add_rule('max_length', '50');

		# postal_code
		$v = $obj_validate->add('postal_code', '郵便番号');
		$v->add_rule('max_length', '100');

		# pref
		$v = $obj_validate->add('pref', '都道府県名');
		$v->add_rule('max_length', '50');

		# locality
		$v = $obj_validate->add('locality', '住所');
		$v->add_rule('max_length', '255');

		# street
		$v = $obj_validate->add('street', '番地');
		$v->add_rule('max_length', '100');

		# profile_fields
		$v = $obj_validate->add('profile_fields', 'プロフィール');
		$v->add_rule('max_length', '2000');

		# facebook_url
		$v = $obj_validate->add('facebook_url', 'フェイスブックURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# google_url
		$v = $obj_validate->add('google_url', 'グーグルURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# twitter_url
		$v = $obj_validate->add('twitter_url', 'TwitterURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# picture_url
		$v = $obj_validate->add('picture_url', '画像URL');
		$v->add_rule('max_length', '255');
		$v->add_rule('valid_url');

		# member_type
		$v = $obj_validate->add('member_type', 'メンバー区分');
		$v->add_rule('exact_length', 1);

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_registdecide()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# auth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# decide_hash
		$v = $obj_validate->add('decide_hash', 'decide_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32');
		$v->add_rule('valid_string', array('numeric', 'alpha'));

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	public static function validation_for_registcheck()
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

		# email
		$v = $obj_validate->add('email', 'email');
		$v->add_rule('valid_email');
		if (\Input::post('email') === 'email')
		{
			$v->add_rule('required');
		}

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_edit()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', 32);
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		# oauth_id
		$v = $obj_validate->add('oauth_id', 'oauth_id');
		$v->add_rule('min_length', '3');
		$v->add_rule('max_length', '255');
		if (\Input::post('oauth_type') !== 'email')
		{
			$v->add_rule('required');
		}

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('max_length', 19);
		$v->add_rule('valid_string', array('nurmeric'));

		# email
		$v = $obj_validate->add('email', 'email');
		$v->add_rule('valid_email');
//		$v->add_rule('required');
		$v->add_rule('check_unique_email_for_edit', static::$_obj_request->oauth_type, static::$_obj_request->user_id); // 独自

		# password
		$v = $obj_validate->add('password', 'password');
		$v->add_rule('max_length', '16');
		$v->add_rule('min_length', '4');
		$v->add_rule('valid_string', array('numeric', 'alpha'));
//		$v->add_rule('required');

		# user_name
		$v = $obj_validate->add('user_name', 'ユーザ名');
		$v->add_rule('max_length', '100');
//		$v->add_rule('required');

		# first_name
		$v = $obj_validate->add('first_name', '名');
		$v->add_rule('max_length', '50');

		# last_name
		$v = $obj_validate->add('last_name', '苗字');
		$v->add_rule('max_length', '50');

		# date
		$v = $obj_validate->add('date', '登録日');
		$v->add_rule('max_length', '10');

		# link
		$v = $obj_validate->add('link', 'リンク');
		$v->add_rule('max_length', '255');
		$v->add_rule('valid_url');

		# gender
		$v = $obj_validate->add('gender', '性別');
		$v->add_rule('max_length', '10');

		# birthday
		$v = $obj_validate->add('birthday', '誕生年月日');
		$v->add_rule('max_length', '100');

		# birthday_year
		$v = $obj_validate->add('birthday_year', '誕生年');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '4');

		# birthday_month
		$v = $obj_validate->add('birthday_month', '誕生月');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '2');
		$v->add_rule('numeric_min', 0);
		$v->add_rule('numeric_max', 12);

		# birthday_day
		$v = $obj_validate->add('birthday_day', '誕生日');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '2');
		$v->add_rule('numeric_min', 0);
		$v->add_rule('numeric_max', 31);

		# birthday_secret
		$v = $obj_validate->add('birthday_secret', '誕生日表示フラグ');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('exact_length', 1);

		# old
		$v = $obj_validate->add('old', '年齢');
		$v->add_rule('valid_string', array('numeric'));

		# old_secret
		$v = $obj_validate->add('old_secret', '年齢表示フラグ');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('exact_length', 1);

		# locale
		$v = $obj_validate->add('locale', 'ロケール');
		$v->add_rule('max_length', '100');

		# country
		$v = $obj_validate->add('country', '国名');
		$v->add_rule('max_length', '50');

		# postal_code
		$v = $obj_validate->add('postal_code', '郵便番号');
		$v->add_rule('max_length', '100');

		# pref
		$v = $obj_validate->add('pref', '都道府県名');
		$v->add_rule('max_length', '50');

		# locality
		$v = $obj_validate->add('locality', '住所');
		$v->add_rule('max_length', '255');

		# street
		$v = $obj_validate->add('street', '番地');
		$v->add_rule('max_length', '100');

		# profile_fields
		$v = $obj_validate->add('profile_fields', 'プロフィール');
		$v->add_rule('max_length', '2000');

		# facebook_url
		$v = $obj_validate->add('facebook_url', 'フェイスブックURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# google_url
		$v = $obj_validate->add('google_url', 'グーグルURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# twitter_url
		$v = $obj_validate->add('twitter_url', 'TwitterURL');
		$v->add_rule('max_length', 255);
		$v->add_rule('valid_url');

		# picture_url
		$v = $obj_validate->add('picture_url', '画像URL');
		$v->add_rule('max_length', '255');
		$v->add_rule('valid_url');

		# member_type
		$v = $obj_validate->add('member_type', 'メンバー区分');
		$v->add_rule('exact_length', 1);

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_editdecide()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# auth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# decide_hash
		$v = $obj_validate->add('decide_hash', 'decide_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32');
		$v->add_rule('valid_string', array('numeric', 'alpha'));

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('exact_length', 32);

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('max_length', 19);
		$v->add_rule('valid_string', array('nurmeric'));

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_password_reissue_request()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# auth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# email
		$v = $obj_validate->add('email', 'email');
		$v->add_rule('valid_email');
		$v->add_rule('required');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_password_reissue_done()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# auth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# reissue_hash
		$v = $obj_validate->add('reissue_hash', 'reissue_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32');
		$v->add_rule('valid_string', array('numeric', 'alpha'));

		# password
		$v = $obj_validate->add('password', 'password');
		$v->add_rule('max_length', '16');
		$v->add_rule('min_length', '4');
		$v->add_rule('valid_string', array('numeric', 'alpha'));
		$v->add_rule('required');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}






	public static function format_for_logincheck()
	{
		\Log::debug('[start]'. __METHOD__);

		static::$oauth_id = '';

		return true;
	}

	/**
	 * oauth or email regist
	 * @throws \Exception
	 * @return boolean
	 */
	public static function transaction_for_set_user_info()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$user_dao->start_transaction();

			if (static::$oauth_type == 'email')
			{
				$arr_user_info = $user_dao->get_userinfo_by_email_and_decide_before(array(
						'oauth_type' => static::$oauth_type,
						'email'      => static::$email,
				));
			}
			else
			{
				$arr_user_info = $user_dao->get_userinfo_by_oauth_and_decide_before(array(
						'oauth_type' => static::$oauth_type,
						'oauth_id'   => static::$oauth_id,
				));
			}
			if ( ! empty($arr_user_info))
			{
				if (current($arr_user_info)->is_decided)
				{
					throw new \Exception('すでに登録済みです。', 7015);
				}
				else
				{
					throw new \Exception('現在登録申請中です。', 7014);
				}
			}

			# ユーザ情報をインサート
			static::$is_decided = "0";
			$arr_result = static::_set_user_table();
			static::$user_id = $arr_result[0];

			# email未確定データのインサート
			static::$decide_hash = md5(\Date::forge()->format('%Y-%m-%d %H:%M:%S'). static::$user_id);
			static::_set_userdecidestatus_table();

			$user_dao->commit_transaction();
			return true;
		}
		catch (\Exception $e)
		{
			$user_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	public static function transaction_for_edit_user_info()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$user_dao->start_transaction();

			# 変更前のデータを取得
			$arr_user_info_before = (array)UserService::get_user_info_from_table_by_user_id(false);
			static::$user_name_before = $arr_user_info_before['user_name'];
			static::$email_before     = $arr_user_info_before['email'];
			static::$password_before  = $arr_user_info_before['password'];
			if (empty(static::$user_name))
			{
				static::$user_name = static::$user_name_before;
			}
			if (empty(static::$email))
			{
				static::$email = static::$email_before;
			}
			if (empty(static::$password))
			{
				static::$password = static::$password_before;
			}

			if ( ! empty(static::$user_name) and static::$user_name_before != static::$user_name)
			{
				static::$is_username_change = true;
			}
			if ( ! empty(static::$password) and static::$password_before != static::$password)
			{
				static::$is_password_change = true;
			}
			if ( ! empty(static::$email) and static::$email_before != static::$email)
			{
				static::$is_email_change = true;
			}

			# メールアドレスのみ変更になった場合はログイン情報は削除しない
			if (static::$is_username_change === true or static::$is_password_change === true)
			{
				# ログイン情報を削除
				$login_dao = new LoginDao();
				if ( ! $login_dao->remove_login_hash_for_reflash(static::$user_id, static::$login_hash))
				{
					throw new \Exception('ログインハッシュの更新に失敗しました。');
				}
			}

			# ユーザ情報を更新
			$arr_update_profile = array();
			if (static::$is_username_change)
			{
				$arr_update_profile['user_name'] = static::$user_name;
			}
			if (static::$is_password_change)
			{
				$arr_update_profile['password'] = static::$password;
			}
			if ( ! empty($arr_update_profile))
			{
				$user_dao->update_profile($arr_update_profile, array('user_id' => static::$user_id));
			}

			# メールアドレスが変更になった場合
			if (static::$is_email_change === true)
			{
				$userdecidestatusdao = new UserDecideStatusDao();
				# ユーザ確定前データで有効期間内のがあったら例外処理
				$arr_expired_data = $userdecidestatusdao->get_expired_by_user_id(static::$user_id);
				if ( ! empty($arr_expired_data))
				{
					throw new \Exception('有効期限内の未確定データが存在します', 7014);
				}

				# ユーザ確定前データ登録に追加
				static::$decide_hash = md5(\Date::forge()->format('%Y-%m-%d %H:%M:%S'). static::$user_id);
				$userdecidestatusdao->set_regist_undecide(array(
						'user_id'     => static::$user_id,
						'email'       => static::$email,
						'decide_hash' => static::$decide_hash,
						'type'        => 'edit',
				));
				if (static::$is_username_change === true or static::$is_password_change === true)
				{
					# ログイン情報を再登録
					LoginService::set(array(
						'email'    => static::$email_before,
						'password' => static::$password,
					));
					LoginService::set_login(true);
				}
			}
			else
			{
				# ログイン情報を再登録
				LoginService::set(array(
					'email'    => static::$email,
					'password' => static::$password,
				));
				LoginService::set_login(true);
			}

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$user_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * oauth or email regist
	 * @throws \Exception
	 * @return boolean
	 */
	public static function transaction_for_set_registdecide()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$userdecidestatusdao = new UserDecideStatusDao();
			$user_dao->start_transaction();

			# 未確定データを取得
			$arr_decide_info = (array)$userdecidestatusdao->get_undecide(array(
					'decide_hash' => static::$decide_hash,
					'type'        => 'regist',
			));
			if (empty($arr_decide_info))
			{
				throw new \Exception('not found decide_hash', 7013);
			}
			static::$user_id = $arr_decide_info['user_id'];

			$access_timestamp = strtotime($arr_decide_info['access_date']);
			$now_timestamp = \Date::forge()->get_timestamp();
			if (($access_timestamp + \Config::get('journal.decide_time_limit')) < $now_timestamp)
			{
				throw new \Exception('decide time over', 7012);
			}

			# ユーザ確定テーブル更新
			$result = $userdecidestatusdao->update_regist_decided(array(
					'id' => $arr_decide_info['id'],
			));

			# ユーザテーブル更新
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
			$arr_profile = array(
				'email'       => $arr_decide_info['email'],
				'is_decided'  => '1',
				'decide_date' => $datetime,
				'last_login'  => $datetime,
			);
			$result = $user_dao->update_profile($arr_profile, array(
				'user_id' => static::$user_id,
			));

			# ユーザ情報を取得
			$arr_user_info = (array)$user_dao->get_userinfo_by_user_id(static::$user_id);
			$arr_user_info['user_id'] = $arr_user_info['id'];
			static::$user_name  = $arr_user_info['user_name'];
			static::$email      = $arr_user_info['email'];
			static::$oauth_id   = $arr_user_info['oauth_id'];
			static::$password_digits = $arr_user_info['password_digits'];
			static::$is_decided = "1";

			# デフォルト領収名を登録
			FixService::set_default_data(static::$user_id);
			# デフォルト支出を登録
			PaymentService::set_default_data(static::$user_id);

			// login_hash値を取得
			$login_hash = LoginService::generate_login_hash();
			$arr_user_info['login_hash'] = $login_hash;
			static::$login_hash = $login_hash;

			// データベースにログイン情報を登録, 同時にlogin_dtoにセット
			$login_dao = new LoginDao();
			$login_dao->set_login($arr_user_info);

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$user_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * oauth or email regist
	 * @throws \Exception
	 * @return boolean
	 */
	public static function transaction_for_set_edit_decide()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$userdecidestatusdao = new UserDecideStatusDao();
			$user_dao->start_transaction();

			# 未確定データを取得
			$arr_decide_info = (array)$userdecidestatusdao->get_undecide(array(
				'decide_hash' => static::$decide_hash,
				'type'        => 'edit',
			));
			if (empty($arr_decide_info))
			{
				throw new \Exception('not found decide_hash');
			}
			static::$user_id = $arr_decide_info['user_id'];

			$access_timestamp = strtotime($arr_decide_info['access_date']);
			$now_timestamp = \Date::forge()->get_timestamp();
			if (($access_timestamp + \Config::get('journal.decide_time_limit')) < $now_timestamp)
			{
				throw new \Exception('decide time over', 7013);
			}

			# ユーザ確定テーブル更新
			$result = $userdecidestatusdao->update_regist_decided(array(
				'id' => $arr_decide_info['id'],
			));

			# ユーザテーブル更新
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
			$arr_profile = array(
					'email'       => $arr_decide_info['email'],
					'is_decided'  => '1',
					'decide_date' => $datetime,
					'last_login'  => $datetime,
			);
			$arr_profile['last_logout'] = $datetime;
			$result = $user_dao->update_profile($arr_profile, array(
					'user_id' => static::$user_id,
			));

			# ユーザ情報を取得
			$arr_user_info = (array)$user_dao->get_userinfo_by_user_id(static::$user_id);
			$arr_user_info['user_id'] = $arr_user_info['id'];
			static::$user_name  = $arr_user_info['user_name'];
			static::$email      = $arr_user_info['email'];
			static::$is_decided = "1";

			// login_hash値を取得
			static::$login_hash = LoginService::generate_login_hash();
			$arr_user_info['login_hash'] = static::$login_hash;
			$arr_user_info['remark'] = 'edit';

			// データベースにログイン情報を登録, 同時にlogin_dtoにセット
			$login_dao = new LoginDao();
			if (isset(static::$user_id))
			{
				$login_dao->remove_login_hash_for_reflash(static::$user_id);
			}
			$login_dao->set_login($arr_user_info);

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$user_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	public static function transaction_for_password_reissue_request()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$user_dao->start_transaction();

			$arr_user_info = $user_dao->get_userinfo_by_email(static::$email, static::$oauth_type);
			if (empty($arr_user_info))
			{
				throw new \Exception('not user by email', 7006);
			}

			$arr_values = array(
					'user_id'  => $arr_user_info['id'],
					'email'    => $arr_user_info['email'],
					'reissue_hash' => md5(\Date::forge()->format('%Y-%m-%d %H:%M:%S'). $arr_user_info['email']. rand(0,499)),
			);
			static::$user_id = $arr_user_info['id'];
			static::$user_name = $arr_user_info['user_name'];
			static::$email   = $arr_user_info['email'];
			static::$reissue_hash = $arr_values['reissue_hash'];
			$password_reissue_status_dao = new PasswordReissueStatusDao();
			$arr_where = array(
				'user_id' => static::$user_id,
				'email'   => static::$email,
			);
			$arr_reissue_status = $password_reissue_status_dao->get_valid_reissue_data($arr_where, 180);
			if ( ! empty($arr_reissue_status))
			{
				throw new \Exception('valid data alive', 7021);
			}
			$password_reissue_status_dao->set_password_reissue_hash($arr_values);

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$user_dao->rollback_transaction();
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	public static function transaction_for_password_reissue_done()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$user_dao = new UserDao();
			$password_reissue_status_dao = new PasswordReissueStatusDao();
			$user_dao->start_transaction();
			$arr_reissue_info = $password_reissue_status_dao->get_unreissue(static::$reissue_hash, static::$oauth_type);

			static::$user_id = $arr_reissue_info['user_id'];
			if (empty($arr_reissue_info))
			{
				throw new \Exception('not reissue user', 7021);
			}

			$arr_user_info_before = (array)UserService::get_user_info_from_table_by_user_id(false);
			static::$password_before  = $arr_user_info_before['password'];
			static::$oauth_id         = $arr_user_info_before['oauth_id'];
			static::$oauth_type       = $arr_user_info_before['oauth_type'];
			static::$email            = $arr_user_info_before['email'];

			# ログイン情報を削除
			if (static::$oauth_type != 'email')
			{
				$login_dao = new LoginDao();
				if ( ! $login_dao->remove_login_hash_for_reflash(static::$user_id))
				{
					throw new \Exception('ログインハッシュの更新に失敗しました。');
				}
			}
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
			$access_timestamp = strtotime($arr_reissue_info['access_date']);
			$now_timestamp = strtotime($datetime);
			if (($access_timestamp + \Config::get('journal.decide_time_limit')) < $now_timestamp)
			{
				throw new \Exception('reissue timeover from request', 7022);
			}
			$password_reissue_status_dao->update_reissue_decided($arr_reissue_info['id']);

			$arr_profile = array(
				'password' => static::$password,
				'password_digits' => static::$password_digits,
				'updated_at' => $datetime,
			);
			$arr_where = array(
					'user_id'    => static::$user_id,
					'email'      => static::$email,
					'oauth_type' => static::$oauth_type,
					'is_decided' => '1',
					'is_deleted' => '0',
					'is_leaved'  => '0',
			);
			$user_dao->update_profile($arr_profile, $arr_where);

			# ログイン情報を再登録
			LoginService::set(array(
				'email'      => static::$email,
				'password'   => static::$password_before,
				'oauth_type' => static::$oauth_type,
				'oauth_id'   => static::$oauth_id,
				'user_id'    => static::$user_id,
			));
			LoginService::set_login(true);

			$user_dao->commit_transaction();

			return true;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$user_dao->rollback_transaction();
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
				'user_id'     => static::$user_id,
				'user_name'   => static::$user_name,
				'email'       => static::$email,
				'password'    => static::$password,
				'password_digits' => static::$password_digits,
				'login_hash'  => static::$login_hash,
				'is_decided'  => static::$is_decided,
				'decide_hash' => static::$decide_hash,
				'oauth_type'  => static::$oauth_type,
				'oauth_id'    => static::$oauth_id,
				'is_password_change' => static::$is_password_change,
				'is_email_change'    => static::$is_email_change,
		);
	}

	/**
	 *
	 * @throws \Exception
	 * @return $user_info stdClass
	 */
	public static function get_user_info_from_table_by_user_id($to_property=true)
	{
		\Log::debug('[start]'. __METHOD__);

		$user_dao = new UserDao();
		if ( ! $obj_user_info = $user_dao->get_userinfo_by_user_id(static::$user_id))
		{
			throw new \Exception('can not get userinfo error', 7007);
		}
		if ($to_property)
		{
			static::$user_id = $obj_user_info->id;
			static::$user_name = $obj_user_info->user_name;
			static::$password_digits = $obj_user_info->password_digits;
			static::$email           = $obj_user_info->email;
		}

		return $obj_user_info;
	}

	public static function get_users()
	{
		\Log::debug('[start]'. __METHOD__);

		$user_dao = new UserDao();
		$arr_users = array();
		foreach ($user_dao->get_users() as $i => $val)
		{
			$arr_users[] = $val->id;
		}
		return $arr_users;
	}

	public static function is_regist()
	{
		try
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
					$strategy = new ConcreateStrategyEmail();
			}
			$obj_login_context = new LoginContext($strategy);
			# ユーザ情報を取得
			$arr_user_info = array(
					'user_id'    => static::$user_id,
					'email'      => static::$email,
					'password'   => static::$password,
					'oauth_id'   => static::$oauth_id,
					'oauth_type' => static::$oauth_type,
			);
			# ユーザ情報を取得
			$arr_regist_info = (array)$obj_login_context->get_user_info($arr_user_info);
			$arr_regist_info = array_filter($arr_regist_info);

			if (empty($arr_regist_info))
			{
				\Log::debug('ユーザ情報を取得できませんでした。');
				return false;
			}
			return $arr_regist_info;
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			if ($e->getCode() == '7010') // ログインハッシュ未存在時
			{
				return false;
			}
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 *
	 * @throws \Exception
	 * @return $user_info stdClass
	 */
	public static function is_exist_user_by_user_id()
	{
		\Log::debug('[start]'. __METHOD__);

		$user_dao = new UserDao();
		if ( ! $user_dao->is_exist_user_by_user_id())
		{
			throw new \Exception('can not get userinfo error', 7007);
		}
		return true;
	}

	public static function send_mail_for_regist_decide()
	{
		\Log::debug('[start]'. __METHOD__);

		try
		{
			$from_email = \Config::get('journal.email.from_address');
			$from_name  = \Config::get('journal.email.from_name');
			$to_email   = static::$email;
			$to_user    = static::$user_name;
			switch (static::$oauth_type)
			{
				case 'email':
					$oauth_type = "メールアドレスログイン";
					break;
				case 'facebook':
					$oauth_type = "Facebookログイン";
					break;
				case 'line':
					$oauth_type = "LINEログイン";
					break;
				case 'google':
					$oauth_type = "Googleログイン";
					break;
				case 'twitter':
					$oauth_type = "Twitterログイン";
					break;
				case 'yahoo':
					$oauth_type = "Yahoo!ログイン";
					break;
				default:
					$oauth_type = "";
			}
			$body = \Date::forge()->format('%Y-%m-%d %H:%M:%S'). "送信". PHP_EOL;
			$body .= "━━━━━━━━━━━━━━━━━━━━━━━━". PHP_EOL. PHP_EOL;
			$body .=  static::$user_name. "様". PHP_EOL. PHP_EOL;
			$body .= '家計簿アプリのペイジャーナルをご利用いただきありがとうございます。'. PHP_EOL;
			$body .= 'ユーザ登録の申請を受け付けました。'. PHP_EOL;
			$body .= '(現時点ではまだ登録は未確定ですのでご注意ください。)'. PHP_EOL. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= '・お名前：'. static::$user_name. PHP_EOL;
			$body .= '・メールアドレス：'. static::$email. PHP_EOL;
			$body .= '・ログイン方法：'. $oauth_type. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL. PHP_EOL;
			$body .= 'こちらのユーザ登録を確定するには以下のリンクへ'. \Config::get('journal.decide_time_min'). '分以内にアクセスしてください。'. PHP_EOL;
			$body .= '◇ペイジャーナルユーザ登録確定ページ'. PHP_EOL;
			$body .= \Config::get('journal.www_host'). '/user/regist/decide/'. static::$oauth_type. '/'. static::$decide_hash. '/';
			$body .= PHP_EOL. PHP_EOL. PHP_EOL. PHP_EOL;
			$body .= "なお". \Config::get('journal.decide_time_min'). "分以上経過してしまった場合は恐れ入りますが、再度ユーザ登録をしていただきますようお願いいたします。". PHP_EOL;
			$body .= '◇ペイジャーナルログインページ'. PHP_EOL;
			$body .= \Config::get('journal.www_host'). '/user/login/'. PHP_EOL. PHP_EOL. PHP_EOL;
			$body .= static::$user_name."様がより良いマネーライフをお送りするためのお手伝いができることを心より願っております。". PHP_EOL;
			$body .= "ご利用お待ちしております。". PHP_EOL. PHP_EOL;
			$body .= '―――――――――――――――――――――――'. PHP_EOL;
			$body .= "□". \Config::get('journal.email.from_name'). PHP_EOL;
			$body .= \Config::get('journal.www_host'). PHP_EOL. PHP_EOL;
			$body .= "□". \Config::get('journal.email.publish'). PHP_EOL;
			$body .= \Config::get('journal.email.from_address'). PHP_EOL;
			$body .= \Config::get('journal.email.copyright'). PHP_EOL;
			\Log::info($body);

			$obj_email = Email::forge('jis');
			$obj_email->from($from_email, $from_name);
			$obj_email->to($to_email);
			$obj_email->priority(\Email::P_HIGH);
			$obj_email->subject('ペイジャーナル -ユーザ登録を受け付けました-');
			$obj_email->body($body);
			$obj_email->send();

			return true;
		}
		catch(\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');

			$env = isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : null;
			if ($env === \Fuel::PRODUCTION)
			{
				throw new \Exception($e);
			}

			\Log::error('stagingでのメール送信は行いません');
			return true;
		}
	}

	public static function send_mail_for_edit_decide()
	{
		\Log::debug('[start]'. __METHOD__);

		try
		{
			if (static::$is_email_change == false)
			{
				\Log::info('メール変更はありません');
				return true;
			}

			$from_email = \Config::get('journal.email.from_address');
			$from_name  = \Config::get('journal.email.from_name');
			$to_email   = static::$email;
			$to_user    = empty(static::$user_name)? static::$user_name_before: static::$user_name;
			switch (static::$oauth_type)
			{
				case 'email':
					$oauth_type = "メールアドレスログイン";
					break;
				case 'facebook':
					$oauth_type = "Facebookログイン";
					break;
				case 'line':
					$oauth_type = "LINEログイン";
					break;
				case 'google':
					$oauth_type = "Googleログイン";
					break;
				case 'twitter':
					$oauth_type = "Twitterログイン";
					break;
				case 'yahoo':
					$oauth_type = "Yahoo!ログイン";
					break;
				default:
					$oauth_type = "";
			}

			$body = \Date::forge()->format('%Y-%m-%d %H:%M:%S'). "送信". PHP_EOL;
			$body .= "━━━━━━━━━━━━━━━━━━━━━━━━". PHP_EOL. PHP_EOL;
			$body .=  $to_user. "様". PHP_EOL. PHP_EOL;
			$body .= '家計簿アプリのペイジャーナルをご利用いただきありがとうございます。'. PHP_EOL;
			$body .= 'ただいまご登録のメールアドレス変更依頼を受け付けました。'. PHP_EOL;
			$body .= '(現時点ではまだ変更は未確定ですのでご注意ください。)'. PHP_EOL. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= '・お名前：'. $to_user. PHP_EOL;
			$body .= '・変更前メールアドレス：'. static::$email_before. PHP_EOL;
			$body .= '・変更後メールアドレス：'. static::$email. PHP_EOL;
			$body .= '・ログイン方法：'. $oauth_type. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= '変更を確定するには以下のリンクに'. \Config::get('journal.decide_time_min'). '分以内にアクセスしてください。'. PHP_EOL;
			$body .= '◇ペイジャーナルユーザ情報変更確定ページ'. PHP_EOL;
			$body .= \Config::get('journal.www_host'). '/user/edit/decide/'. static::$oauth_type. '/'. static::$decide_hash. '/';
			$body .= PHP_EOL. PHP_EOL. PHP_EOL;
			$body .= "なお". \Config::get('journal.decide_time_min'). "分以上経過してしまった場合は恐れ入りますが、再度ユーザ情報の変更手続きをしていただきますようお願いいたします。". PHP_EOL;
			$body .= '◇ペイジャーナルユーザ情報変更ページ'. PHP_EOL;
			$body .= \Config::get('journal.www_host'). '/user/edit/'. PHP_EOL. PHP_EOL. PHP_EOL;
			$body .= "これからも". $to_user."様がより良いマネーライフをお送りするためのお手伝いができることを心より願っております。". PHP_EOL;
			$body .= "引き続きご利用よろしくお願いいたします。". PHP_EOL. PHP_EOL;
			$body .= '―――――――――――――――――――――――'. PHP_EOL;
			$body .= "□". \Config::get('journal.email.from_name'). PHP_EOL;
			$body .= \Config::get('journal.www_host'). PHP_EOL. PHP_EOL;
			$body .= "□". \Config::get('journal.email.publish'). PHP_EOL;
			$body .= \Config::get('journal.email.from_address'). PHP_EOL;
			$body .= \Config::get('journal.email.copyright'). PHP_EOL;
			\Log::info($body);

			$obj_email = Email::forge('jis');
			$obj_email->from($from_email, $from_name);
			$obj_email->to($to_email);
			$obj_email->priority(\Email::P_HIGH);
			$obj_email->subject('ペイジャーナル -ご登録メールアドレスの変更を受け付けました-');
			$obj_email->body($body);
			$obj_email->send();

			return true;
		}
		catch(\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');

			$env = isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : null;
			if ($env === \Fuel::PRODUCTION)
			{
				throw new \Exception($e);
			}

			\Log::error('stagingでのメール送信は行いません');
			return true;
		}
	}

	public static function send_mail_for_edit_password()
	{
		\Log::debug('[start]'. __METHOD__);

		try
		{
			if (static::$is_password_change == false)
			{
				\Log::info('パスワード変更はありません');
				return true;
			}

			$from_email = \Config::get('journal.email.from_address');
			$from_name  = \Config::get('journal.email.from_name');
			$to_email   = static::$email;
			$to_user    = empty(static::$user_name)? static::$user_name_before: static::$user_name;
			switch (static::$oauth_type)
			{
				case 'email':
					$oauth_type = "メールアドレスログイン";
					break;
				case 'facebook':
					$oauth_type = "Facebookログイン";
					break;
				case 'line':
					$oauth_type = "LINEログイン";
					break;
				case 'google':
					$oauth_type = "Googleログイン";
					break;
				case 'twitter':
					$oauth_type = "Twitterログイン";
					break;
				case 'yahoo':
					$oauth_type = "Yahoo!ログイン";
					break;
				default:
					$oauth_type = "";
			}

			$body = \Date::forge()->format('%Y-%m-%d %H:%M:%S'). "送信". PHP_EOL;
			$body .= "━━━━━━━━━━━━━━━━━━━━━━━━". PHP_EOL. PHP_EOL;
			$body .=  $to_user. "様". PHP_EOL. PHP_EOL;
			$body .= '家計簿アプリのペイジャーナルをご利用いただきありがとうございます。'. PHP_EOL;
			$body .= 'ただいまご登録のパスワードの変更依頼を受け付け処理を完了いたしました。'. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= '・お名前：'. $to_user. PHP_EOL;
			$body .= '・メールアドレス：'. static::$email. PHP_EOL;
			$body .= '・ログイン方法：'. $oauth_type. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= "これからも". $to_user."様がより良いマネーライフをお送りするためのお手伝いができることを心より願っております。". PHP_EOL;
			$body .= "引き続きご利用よろしくお願いいたします。". PHP_EOL. PHP_EOL;
			$body .= '―――――――――――――――――――――――'. PHP_EOL;
			$body .= "□". \Config::get('journal.email.from_name'). PHP_EOL;
			$body .= \Config::get('journal.www_host'). PHP_EOL. PHP_EOL;
			$body .= "□". \Config::get('journal.email.publish'). PHP_EOL;
			$body .= \Config::get('journal.email.from_address'). PHP_EOL;
			$body .= \Config::get('journal.email.copyright'). PHP_EOL;
			\Log::info($body);

			$obj_email = Email::forge('jis');
			$obj_email->from($from_email, $from_name);
			$obj_email->to($to_email);
			$obj_email->priority(\Email::P_HIGH);
			$obj_email->subject('ペイジャーナル -ご登録パスワードの変更を受け付けました-');
			$obj_email->body($body);
			$obj_email->send();

			return true;
		}
		catch(\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');

			$env = isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : null;
			if ($env === \Fuel::PRODUCTION)
			{
				throw new \Exception($e);
			}

			\Log::error('stagingでのメール送信は行いません');
			return true;
		}
	}

	public static function send_mail_for_password_reissue()
	{
		\Log::debug('[start]'. __METHOD__);

		try
		{
			$from_email = \Config::get('journal.email.from_address');
			$from_name  = \Config::get('journal.email.from_name');
			$to_email   = static::$email;
			$to_user    = empty(static::$user_name)? static::$user_name_before: static::$user_name;
			switch (static::$oauth_type)
			{
				case 'email':
					$oauth_type = "メールアドレスログイン";
					break;
				case 'facebook':
					$oauth_type = "Facebookログイン";
					break;
				case 'line':
					$oauth_type = "LINEログイン";
					break;
				case 'google':
					$oauth_type = "Googleログイン";
					break;
				case 'twitter':
					$oauth_type = "Twitterログイン";
					break;
				case 'yahoo':
					$oauth_type = "Yahoo!ログイン";
					break;
				default:
					$oauth_type = "";
			}

			$body = \Date::forge()->format('%Y-%m-%d %H:%M:%S'). "送信". PHP_EOL;
			$body .= "━━━━━━━━━━━━━━━━━━━━━━━━". PHP_EOL. PHP_EOL;
			$body .=  $to_user. "様". PHP_EOL. PHP_EOL;
			$body .= '家計簿アプリのペイジャーナルをご利用いただきありがとうございます。'. PHP_EOL;
			$body .= 'ご登録のパスワード再設定依頼を受け付けました。'. PHP_EOL;
			$body .= '(現時点ではまだ変更は未確定ですのでご注意ください。)'. PHP_EOL. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL;
			$body .= '・お名前：'. $to_user. PHP_EOL;
			$body .= '・メールアドレス：'. static::$email. PHP_EOL;
			$body .= '・ログイン方法：'. $oauth_type. PHP_EOL;
			$body .= "----------------------------------------------". PHP_EOL. PHP_EOL;

			$body .= 'パスワードを再設定を確定するには以下のリンクに'. \Config::get('journal.decide_time_min'). '分以内にアクセスしてください。'. PHP_EOL;
			$body .= \Config::get('journal.www_host'). '/user/password/reissueform/'. static::$oauth_type. '/'. static::$reissue_hash. '/';
			$body .= PHP_EOL. PHP_EOL. PHP_EOL;
			$body .= "なお". \Config::get('journal.decide_time_min'). "分以上経過してしまった場合は恐れ入りますが、再度パスワード設定変更手続きをしていただきますようお願いいたします。". PHP_EOL. PHP_EOL;
			$body .= "これからも". $to_user."様がより良いマネーライフをお送りするためのお手伝いができることを心より願っております。". PHP_EOL;
			$body .= "引き続きご利用よろしくお願いいたします。". PHP_EOL. PHP_EOL;
			$body .= '―――――――――――――――――――――――'. PHP_EOL;
			$body .= "□". \Config::get('journal.email.from_name'). PHP_EOL;
			$body .= \Config::get('journal.www_host'). PHP_EOL. PHP_EOL;
			$body .= "□". \Config::get('journal.email.publish'). PHP_EOL;
			$body .= \Config::get('journal.email.from_address'). PHP_EOL;
			$body .= \Config::get('journal.email.copyright'). PHP_EOL;
			\Log::info($body);

			$obj_email = Email::forge('jis');
			$obj_email->from($from_email, $from_name);
			$obj_email->to($to_email);
			$obj_email->priority(\Email::P_HIGH);
			$obj_email->subject('ペイジャーナル -パスワード設定変更依頼を受け付けました-');
			$obj_email->body($body);
			$obj_email->send();

			return true;
		}
		catch(\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');

			$env = isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : null;
			if ($env === \Fuel::PRODUCTION)
			{
				throw new \Exception($e);
			}

			\Log::error('stagingでのメール送信は行いません');
			return true;
		}
	}



	private static function _set_user_table()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_request = array();
		$arr_request['user_name']       = static::$user_name;
		$arr_request['first_name']      = static::$first_name;
		$arr_request['last_name']       = static::$last_name;
		$arr_request['password']        = static::$password;
		$arr_request['password_digits'] = static::$password_digits;
		$arr_request['email']           = static::$email;
		$arr_request['date']            = \Date::forge()->format('%Y-%m-%d');
		$arr_request['link']            = static::$link;
		$arr_request['gender']          = static::$gender;
		$arr_request['birthday']        = static::$birthday;
		$arr_request['birthday_year']   = static::$birthday_year;
		$arr_request['birthday_month']  = static::$birthday_month;
		$arr_request['birthday_day']    = static::$birthday_day;
		$arr_request['birthday_secret'] = static::$birthday_secret;
		$arr_request['old']             = static::$old;
		$arr_request['old_secret']      = static::$old_secret;
		$arr_request['locale']          = static::$locale;
		$arr_request['country']         = static::$country;
		$arr_request['postal_code']     = static::$postal_code;
		$arr_request['pref']            = static::$pref;
		$arr_request['locality']        = static::$locality;
		$arr_request['street']          = static::$street;
		$arr_request['profile_fields']  = static::$profile_fields;
		$arr_request['facebook_url']    = static::$facebook_url;
		$arr_request['google_url']      = static::$google_url;
		$arr_request['twitter_url']     = static::$twitter_url;
		$arr_request['oauth_type']      = static::$oauth_type;
		$arr_request['oauth_id']        = static::$oauth_id;
		$arr_request['picture_url']     = static::$picture_url;
		$arr_request['member_type']     = static::$member_type;
		$arr_request['is_decided']      = static::$is_decided;
		$arr_request['decide_date']     = static::$decide_date;
		$arr_request['last_login']      = static::$last_login;

		$dao = new UserDao();
		$arr_result = $dao->set_profile($arr_request);
		if ($arr_result === false)
		{
			throw new \Exception('can not user insert', 8001);
		}

		return $arr_result;
	}

	private static function _set_userdecidestatus_table()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_request = array();
		$arr_request['user_id'] = static::$user_id;
		$arr_request['email']   = static::$email;
		$arr_request['decide_hash'] = static::$decide_hash;
		$arr_request['type']    = 'regist';

		$dao = new UserDecideStatusDao();
		$arr_result = $dao->set_regist_undecide($arr_request);
		if ($arr_result === false)
		{
			throw new \Exception('can not user insert', 8001);
		}

		return $arr_result;
	}

}