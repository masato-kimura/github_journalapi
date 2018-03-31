<?php
namespace service;

use model\dao\FixDao;
use model\dao\model\dao;
class FixService extends Service
{
	private static $user_id;
	private static $id;
	private static $name;
	private static $remark;
	private static $sort;
	private static $sorted = array(); // 並び順配列 array([sort] => id)
	private static $is_fix  = 0;
	private static $is_disp = 1;
	private static $to_aggre = 0;

	public static function set_request()
	{
		\Log::debug('[start]'. __METHOD__);

		foreach (static::$_obj_request as $key => $val)
		{
			if (property_exists('service\FixService', $key))
			{
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
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_list()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_sort()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
//		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		/* API共通バリデート設定 */
		//		static::_validate_base($obj_validate);

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$arr_sorted = array();
		foreach (static::$_obj_request->sorted as $i => $val)
		{
			#sort配列
			$v = $obj_validate->add('sorted_'. $i);
			$v->add_rule('required');
			$v->add_rule('valid_string', array('numeric'));
			$v->add_rule('max_length', 19);
			# バリデーション用リクエスト配列
			$arr_sorted['sorted_'. $i] = $val;
		}
		$validate_params = $arr_sorted;
		$validate_params['user_id'] = static::$_obj_request->user_id;
		$validate_params['oauth_type'] = static::$_obj_request->oauth_type;
		$validate_params['login_hash'] = static::$_obj_request->login_hash;

		# バリデート実行
		static::_validate_run($obj_validate, $validate_params);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_add()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		# name
		$v = $obj_validate->add('name', 'name');
		$v->add_rule('required');
		$v->add_rule('max_length', 50);

		# remark
		$v = $obj_validate->add('remark', 'remark');
		$v->add_rule('max_length', 1000);

		# is_fix
		$v = $obj_validate->add('is_fix', 'is_fix');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# is_disp
		$v = $obj_validate->add('is_disp', 'is_disp');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# to_aggre
		$v = $obj_validate->add('to_aggre', 'to_aggre');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# sort
		$v = $obj_validate->add('sort', 'sort');
		$v->add_rule('max_length', 3);
		$v->add_rule('valid_string', array('numeric'));

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

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		#id
		$v = $obj_validate->add('id', 'id');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# name
		$v = $obj_validate->add('name', 'name');
		$v->add_rule('required');
		$v->add_rule('max_length', 50);

		# remark
		$v = $obj_validate->add('remark', 'remark');
		$v->add_rule('max_length', 1000);

		# is_fix
		$v = $obj_validate->add('is_fix', 'is_fix');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# is_disp
		$v = $obj_validate->add('is_disp', 'is_disp');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# to_aggre
		$v = $obj_validate->add('to_aggre', 'to_aggre');
		$v->add_rule('max_length', 1);
		$v->add_rule('valid_string', array('numeric'));

		# sort
		$v = $obj_validate->add('sort', 'sort');
		$v->add_rule('max_length', 3);
		$v->add_rule('valid_string', array('numeric'));

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}

	/**
	 * バリデーション
	 * @throws \Exception
	 * @return boolean
	 */
	public static function validation_for_remove()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデートで使用するため obj_requestの値を$_POSTにセットする
		static::_set_request_to_post(static::$_obj_request);

		$obj_validate = \Validation::forge();

		/* 個別バリデート設定 */
		$obj_validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$v = $obj_validate->add('user_id', 'ユーザID');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# oauth_type
		$v = $obj_validate->add('oauth_type', 'oauth_type');
		$v->add_rule('required');
		$v->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');

		# login_hash
		$v = $obj_validate->add('login_hash', 'login_hash');
		$v->add_rule('required');
		$v->add_rule('exact_length', '32'); // md5
		$v->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		#id
		$v = $obj_validate->add('id', 'id');
		$v->add_rule('required');
		$v->add_rule('valid_string', array('numeric'));
		$v->add_rule('max_length', '19');

		# バリデート実行
		static::_validate_run($obj_validate);

		return true;
	}



	public static function get_list()
	{
		\Log::debug('[start]'. __METHOD__);

		$obj_fix_dao = new FixDao();
		$list = $obj_fix_dao->get_list(array(
				'user_id' => static::$user_id,
		));

		return $list;
	}

	public static function set_sorted()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$fix_dao = new FixDao();
			$fix_dao->start_transaction();
			foreach (static::$sorted as $sort => $id)
			{
				$arr_where = array(
						'id' => $id,
						'user_id' => static::$user_id,
				);
				$fix_dao->set_sorted($sort, $arr_where);
			}
			$fix_dao->commit_transaction();
		}
		catch (\Exception $e)
		{
			$fix_dao->rollback_transaction();
			\Log::error($e);
			throw new \Exception($e);
		}
	}

	public static function add_data()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$fix_dao = new FixDao();
			$arr_values = array(
					'user_id'  => static::$user_id,
					'name'     => static::$name,
					'remark'   => static::$remark,
					'is_fix'   => static::$is_fix,
					'is_disp'  => static::$is_disp,
					'to_aggre' => static::$to_aggre,
					'sort'     => static::$sort,
			);
			return $fix_dao->add_data($arr_values);

		}
		catch (\Exception $e)
		{
			$fix_dao->rollback_transaction();
			\Log::error($e);
			throw new \Exception($e);
		}
	}

	public static function set_default_data($user_id)
	{
		try
		{
			$arr_fix_data = array();
			$arr_fix_data[1] = '家賃';
			$arr_fix_data[2] = '光熱費';
			$arr_fix_data[3] = '通信費';
			$arr_non_fix_data = array();
			$arr_non_fix_data[4]  = '交通費';
			$arr_non_fix_data[5]  = '飲食費';
			$arr_non_fix_data[7]  = '備品';
			$arr_non_fix_data[6]  = '衣料品';
			$arr_non_fix_data[8]  = '保険';
			$arr_non_fix_data[9]  = '税金';
			$arr_non_fix_data[10] = '医療費';
			$arr_non_fix_data[11] = '交際費';
			$arr_non_fix_data[12] = '娯楽費';

			$arr_values = array();
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
			$sort = 0;
			foreach ($arr_fix_data as $key => $val)
			{
				$arr_values[] = array(
						'user_id'  => $user_id,
						'name'     => $val,
						'is_fix'   => true,
						'is_disp'  => true,
						'to_aggre' => true,
						'sort'     => ++$sort,
						'no'       => $key,
						'created_at' => $datetime,
						'updated_at' => $datetime,
				);
			}
			foreach ($arr_non_fix_data as $key => $val)
			{
				$arr_values[] = array(
						'user_id'  => $user_id,
						'name'     => $val,
						'is_fix'   => false,
						'is_disp'  => true,
						'to_aggre' => true,
						'sort'     => ++$sort,
						'no'       => $key,
						'created_at' => $datetime,
						'updated_at' => $datetime,
				);
			}
			$fix_dao = new FixDao();
			$result = $fix_dao->add_multi_data($arr_values);
			return $result;
		}
		catch (\Exception $e)
		{
			\Log::error($e);
			throw new \Exception($e);
		}
	}

	public static function edit_data()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$fix_dao = new FixDao();
			$arr_values = array(
					'name'     => static::$name,
					'remark'   => static::$remark,
					'is_fix'   => static::$is_fix,
					'is_disp'  => static::$is_disp,
					'to_aggre' => static::$to_aggre,
			);
			$arr_where = array(
					'id'      => static::$id,
					'user_id' => static::$user_id,
			);
			return $fix_dao->edit_data($arr_values, $arr_where);
		}
		catch (\Exception $e)
		{
			$fix_dao->rollback_transaction();
			\Log::error($e);
			throw new \Exception($e);
		}
	}

	public static function remove_data()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$fix_dao = new FixDao();
			$arr_values = array(
					'is_deleted' => '1',
			);
			$arr_where = array(
					'id'      => static::$id,
					'user_id' => static::$user_id,
			);
			return $fix_dao->remove_data($arr_values, $arr_where);
		}
		catch (\Exception $e)
		{
			$fix_dao->rollback_transaction();
			\Log::error($e);
			throw new \Exception($e);
		}
	}

}