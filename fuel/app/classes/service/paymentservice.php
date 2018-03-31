<?php
namespace service;

use model\dao\PaymentDao;
use model\dao\PaymentReserveDao;
class PaymentService extends Service
{
	# 登録領収名のその他で使用するfix_id
	const OTHER_FIX_ID = '999999999999999999';
	const EVERY_TYPE_YEAR  = '3';
	const EVERY_TYPE_MONTH = '2';
	const EVERY_TYPE_WEEK  = '1';
	const EVERY_TYPE_DAY   = '0';

 	private static $id;
 	private static $payment_reserve_id;
	private static $user_id;
	private static $fix_id;
	private static $is_fix;
	private static $name;
	private static $detail;
	private static $shop;
	private static $date;
	private static $payment_reserve_status;
	private static $date_from;
	private static $date_to;
	private static $every_type;
	private static $every_month_selected;
	private static $every_day_selected;
	private static $every_dayofweek_selected;
	private static $cost;
	private static $remark;
	private static $work_side_per;
	private static $use_type;
	private static $paymethod_id;
	private static $sort;
	private static $year;
	private static $month;
	private static $day;
	private static $search;
	private static $offset;
	private static $limit;
	private static $page;
	private static $sort_by;
	private static $direction;

	public static function set_request($id='')
	{
		\Log::debug('[start]'. __METHOD__);

		static::$id = $id;
		foreach (static::$_obj_request as $key => $val)
		{
			if (property_exists('service\PaymentService', $key))
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
		static::$offset    = isset(static::$offset)? static::$offset: '0';
		static::$limit     = isset(static::$limit)?  static::$limit: '100';
		static::$sort      = isset(static::$sort)?   static::$sort: 'date';
		static::$direction = isset(static::$direction)? static::$direction: 'DESC';
		return true;
	}

	public static function set_request_for_reservelist($id='')
	{
		\Log::debug('[start]'. __METHOD__);

		static::$id = $id;
		foreach (static::$_obj_request as $key => $val)
		{
			if (property_exists('service\PaymentService', $key))
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
		static::$offset    = isset(static::$offset)? static::$offset: '0';
		static::$limit     = isset(static::$limit)?  static::$limit: '100';
		static::$sort      = isset(static::$sort)?   static::$sort: 'id';
		static::$direction = isset(static::$direction)? static::$direction: 'DESC';
		return true;
	}


	public static function validation_for_add()
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		$validation->add('user_id', 'user_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);


		$validation->add('fix_id', 'fix_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('is_fix', 'is_fix')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 1);
		$validation->add('name', 'name')
			->add_rule('required')
			->add_rule('max_length', 50);
		$validation->add('detail', 'detail')
//			->add_rule('required')
			->add_rule('max_length', 50);
		$validation->add('date', 'date')
			->add_rule('required')
			->add_rule('valid_date', 'Y-m-d');
		$validation->add('cost', 'cost')
			->add_rule('required')
			->add_rule('max_length', 11)
			->add_rule('valid_string', array('numeric'));
		$validation->add('work_side_per', 'work_side_per')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 3)
			->add_rule('required');
		$validation->add('use_type', 'use_type')
			->add_rule('valid_string', array('numeric'))
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3)
			->add_rule('required');
		$validation->add('paymethod_id', 'paymethod_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$validation->add('sort', 'sort')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('shop', 'shop')
			->add_rule('max_length', 50);
		$validation->add('remark', 'remark')
			->add_rule('max_length', 1000);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}

	public static function validation_for_edit($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);

		$validation->add('id', 'id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$validation->add('fix_id', 'fix_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('is_fix', 'is_fix')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 1);
		$validation->add('name', 'name')
			->add_rule('max_length', 30)
			->add_rule('required');
		$validation->add('detail', 'detail')
			->add_rule('max_length', 30);
//			->add_rule('required');
		$validation->add('shop', 'shop')
			->add_rule('max_length', 30);
//			->add_rule('required');
		$validation->add('date', 'date')
			->add_rule('valid_string', array('numeric', 'dashes'))
			->add_rule('required');
		$validation->add('cost', 'cost')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required');
		$validation->add('remark', 'remark')
			->add_rule('max_length', 1000);
		$validation->add('work_side_per', 'work_side_per')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required');
		$validation->add('use_type', 'use_type')
			->add_rule('valid_string', array('numeric'))
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3)
			->add_rule('required');
		$validation->add('paymethod_id', 'paymethod_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$validation->add('payment_reserve_status', 'payment_reserve_status')
			->add_rule('valid_string', array('numeric'))
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 1);

		$arr_object = (array)static::$_obj_request;
		$arr_object['id'] = $id;
		if ( ! $validation->run($arr_object))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}


	public static function validation_for_remove($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);

		$validation->add('id', 'id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));

		$arr_validation_values = (array)static::$_obj_request;
		$arr_validation_values['id'] = $id;
		if ( ! $validation->run($arr_validation_values))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}

	public static function validation_for_count()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデーション
		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('year', 'year')
			->add_rule('exact_length', 4)
			->add_rule('valid_string', array('numeric'));
		$validation->add('month', 'month')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('day', 'day')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('search', 'search')
			->add_rule('max_length', 100);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}

	public static function validation_for_list()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデーション
		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('year', 'year')
			->add_rule('exact_length', 4)
			->add_rule('valid_string', array('numeric'));
		$validation->add('month', 'month')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('day', 'day')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('search', 'search')
			->add_rule('max_length', 100);
		$validation->add('offset', 'offset')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 10);
		$validation->add('limit', 'limit')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 10);
		$validation->add('sort_by', 'sort_by')
			->add_rule('valid_string', array('numeric', 'dashes', 'alpha'))
			->add_rule('max_length', 20);
		$validation->add('direction', 'direction')
			->add_rule('valid_string', array('alpha'))
			->add_rule('max_length', 10);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $i => $error)
			{
				\Log::error($error->get_message());
				\Log::error($i);
				throw new \Exception($error->get_message());
			}
		}
	}

	public static function validation_for_detail($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('id', 'id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));

		$arr_validation_values = (array)static::$_obj_request;
		$arr_validation_values['id'] = $id;
		if ( ! $validation->run($arr_validation_values))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
	}


	public static function validation_for_reservelist()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデーション
		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('year', 'year')
			->add_rule('exact_length', 4)
			->add_rule('valid_string', array('numeric'));
		$validation->add('month', 'month')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('day', 'day')
			->add_rule('exact_length', 2)
			->add_rule('valid_string', array('numeric'));
		$validation->add('search', 'search')
			->add_rule('max_length', 100);
		$validation->add('offset', 'offset')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 10);
		$validation->add('limit', 'limit')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 10);
		$validation->add('sort_by', 'sort_by')
			->add_rule('valid_string', array('numeric', 'dashes', 'alpha'))
			->add_rule('max_length', 20);
		$validation->add('direction', 'direction')
			->add_rule('valid_string', array('alpha'))
			->add_rule('max_length', 10);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $i => $error)
			{
				\Log::error($error->get_message());
				\Log::error($i);
				throw new \Exception($error->get_message());
			}
		}
	}


	public static function validation_for_reservedetail($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
		->add_rule('required')
		->add_rule('valid_string', array('numeric'))
		->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
		->add_rule('required')
		->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
		->add_rule('required')
		->add_rule('max_length', '32') // md5
		->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('id', 'id')
		->add_rule('required')
		->add_rule('valid_string', array('numeric'));

		$arr_validation_values = (array)static::$_obj_request;
		$arr_validation_values['id'] = $id;
		if ( ! $validation->run($arr_validation_values))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
	}


	public static function validation_for_reservecount()
	{
		\Log::debug('[start]'. __METHOD__);

		# バリデーション
		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
		->add_rule('required')
		->add_rule('valid_string', array('numeric'))
		->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
		->add_rule('required')
		->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
		->add_rule('required')
		->add_rule('max_length', '32') // md5
		->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type); // 独自

		$validation->add('year', 'year')
		->add_rule('exact_length', 4)
		->add_rule('valid_string', array('numeric'));
		$validation->add('month', 'month')
		->add_rule('exact_length', 2)
		->add_rule('valid_string', array('numeric'));
		$validation->add('day', 'day')
		->add_rule('exact_length', 2)
		->add_rule('valid_string', array('numeric'));
		$validation->add('search', 'search')
		->add_rule('max_length', 100);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}


	public static function validation_for_reserveadd()
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		$validation->add('user_id', 'user_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);
		$validation->add('fix_id', 'fix_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('is_fix', 'is_fix')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 1);
		$validation->add('name', 'name')
			->add_rule('required')
			->add_rule('max_length', 50);
		$validation->add('detail', 'detail')
			//			->add_rule('required')
			->add_rule('max_length', 50);
		$validation->add('every_type', '定期間隔')
			->add_rule('required')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3);
			//->add_rule('match_pattern', '/(year)|(month)|(week)|(day)/');
		$validation->add('every_month_selected', '定期月選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 12);
		$validation->add('every_day_selected', '定期日選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 31);
		$validation->add('every_dayofweek_selected', '定期曜日選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 6);
		$validation->add('date_from', 'date_from')
			->add_rule('required')
			->add_rule('valid_date', 'Y-m-d');
		$validation->add('date_to', 'date_to')
			->add_rule('required')
			->add_rule('valid_date', 'Y-m-d');
		$validation->add('cost', 'cost')
			->add_rule('required')
			->add_rule('max_length', 11)
			->add_rule('valid_string', array('numeric'));
		$validation->add('work_side_per', 'work_side_per')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 3)
			->add_rule('required');
		$validation->add('use_type', 'use_type')
			->add_rule('valid_string', array('numeric'))
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3)
			->add_rule('required');
		$validation->add('paymethod_id', 'paymethod_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$validation->add('sort', 'sort')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('shop', 'shop')
			->add_rule('max_length', 50);
		$validation->add('remark', 'remark')
			->add_rule('max_length', 1000);

		if ( ! $validation->run((array)static::$_obj_request))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}

		switch (static::$_obj_request->every_type)
		{
			case "year":
				if (empty(static::$_obj_request->every_month_selected))
				{
					$message = "定期月が入力されていません";
					throw new \Exception($message);
				}
				if (empty(static::$_obj_request->every_day_selected))
				{
					$message = "定期日が入力されていません";
					throw new \Exception($message);
				}
				break;
			case "month":
				if (empty(static::$_obj_request->every_day_selected))
				{
					$message = "定期日が入力されていません";
					throw new \Exception($message);
				}
				break;
			case "week":
				if (static::$_obj_request->every_dayofweek_selected === "" or
					static::$_obj_request->every_dayofweek_selected === NULL)
				{
					$message = "定期曜日が入力されていません";
					throw new \Exception($message);
				}
				break;
		}

		return true;
	}


	public static function validation_for_reserveedit($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);

		$validation->add('id', 'id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));
		$validation->add('fix_id', 'fix_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 19);
		$validation->add('is_fix', 'is_fix')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 1);
		$validation->add('name', 'name')
			->add_rule('max_length', 30)
			->add_rule('required');
		$validation->add('detail', 'detail')
			->add_rule('max_length', 30);
		//			->add_rule('required');
		$validation->add('shop', 'shop')
			->add_rule('max_length', 30);
		//			->add_rule('required');
		$validation->add('every_type', '定期間隔')
			->add_rule('required')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3);
			//->add_rule('match_pattern', '/(year)|(month)|(week)|(day)/');
		$validation->add('every_month_selected', '定期月選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 12);
		$validation->add('every_day_selected', '定期日選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 31);
		$validation->add('every_dayofweek_selected', '定期曜日選択')
			->add_rule('valid_string', 'numeric')
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 6);
		$validation->add('date_from', 'date_from')
			->add_rule('valid_string', array('numeric', 'dashes'))
			->add_rule('required');
		$validation->add('date_to', 'date_to')
			->add_rule('valid_string', array('numeric', 'dashes'))
			->add_rule('required');
		$validation->add('cost', 'cost')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required');
		$validation->add('remark', 'remark')
			->add_rule('max_length', 1000);
		$validation->add('work_side_per', 'work_side_per')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required');
		$validation->add('use_type', 'use_type')
			->add_rule('valid_string', array('numeric'))
			->add_rule('numeric_min', 0)
			->add_rule('numeric_max', 3)
			->add_rule('required');
		$validation->add('paymethod_id', 'paymethod_id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));

		$arr_object = (array)static::$_obj_request;
		$arr_object['id'] = $id;
		$obj_date_from = new \DateTime($arr_object['date_from']);
		$obj_date_to   = new \DateTime($arr_object['date_to']);
		if ($obj_date_from->getTimestamp() > $obj_date_to->getTimestamp())
		{
			throw new \Exception("有効期間の設定が異常 ". static::$date_from. "-". static::$date_to);
		}
		if ( ! $validation->run($arr_object))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}



	public static function validation_for_reserveremove($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$validation = \Validation::forge();

		/* 個別バリデート設定 */
		$validation->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# user_id
		$validation->add('user_id', 'ユーザID')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', '19');
		# oauth_type
		$validation->add('oauth_type', 'oauth_type')
			->add_rule('required')
			->add_rule('match_pattern', '/(email)|(facebook)|(line)|(google)|(twitter)|(yahoo)/');
		# login_hash
		$validation->add('login_hash', 'login_hash')
			->add_rule('required')
			->add_rule('max_length', '32') // md5
			->add_rule('check_login_hash', static::$_obj_request->user_id, static::$_obj_request->oauth_type);

		$validation->add('id', 'id')
			->add_rule('required')
			->add_rule('valid_string', array('numeric'));

		$arr_validation_values = (array)static::$_obj_request;
		$arr_validation_values['id'] = $id;
		if ( ! $validation->run($arr_validation_values))
		{
			foreach ($validation->error() as $error)
			{
				\Log::error($error->get_message());
				throw new \Exception($error->get_message());
			}
		}
		return true;
	}



	public static function add_data()
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$result = $payment_dao->create(array(
				'user_id'   => static::$user_id,
				'fix_id'    => static::$fix_id,
				'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
				'name'      => static::$name,
				'detail'    => static::$detail,
				'shop'      => static::$shop,
				'date'      => static::$date,
				'cost'      => static::$cost,
				'remark'    => static::$remark,
				'work_side_per' => static::$work_side_per,
				'use_type'      => static::$use_type,
				'paymethod_id'  => static::$paymethod_id,
				'created_at'    => $datetime,
				'updated_at'    => $datetime,
		));
		return $result;
	}


	public static function reserveadd_data()
	{
		\Log::debug('[start]'. __METHOD__);

		try
		{
			$payment_reserve_dao = new PaymentReserveDao();
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');

			$payment_reserve_dao->start_transaction();
			$year  = \Date::forge()->format('%Y');
			$month = ! empty(static::$every_month_selected)? static::$every_month_selected: \Date::forge()->format('%m');
			$day   = ! empty(static::$every_day_selected)? static::$every_day_selected: \Date::forge()->format('%d');
			if ((int)$month > 12 || (int)$day > 31)
			{
				throw new \Exception($year. "-". $month. "-". $day. "日付指定が不正です");
			}

			list(static::$payment_reserve_id, $count) = $payment_reserve_dao->create(array(
					'user_id'   => static::$user_id,
					'fix_id'    => static::$fix_id,
					'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
					'name'      => static::$name,
					'detail'    => static::$detail,
					'shop'      => static::$shop,
					'date_from' => static::$date_from,
					'date_to'   => static::$date_to,
					'every_type' => static::$every_type,
					'every_month_selected'     => static::$every_month_selected,
					'every_day_selected'       => static::$every_day_selected,
					'every_dayofweek_selected' => static::$every_dayofweek_selected,
					'cost'      => static::$cost,
					'remark'    => static::$remark,
					'work_side_per' => static::$work_side_per,
					'use_type'      => static::$use_type,
					'paymethod_id'  => static::$paymethod_id,
					'created_at'    => $datetime,
					'updated_at'    => $datetime,
			));

			switch (static::$every_type)
			{
				case static::EVERY_TYPE_YEAR:
					$result = static::set_year_reserveadd();
					break;
				case static::EVERY_TYPE_MONTH:
					$result = static::set_month_reserveadd();
					break;
				case static::EVERY_TYPE_WEEK:
					$result = static::set_week_reserveadd();
					break;
				case static::EVERY_TYPE_DAY:
					$result = static::set_day_reserveadd();
					break;
			}

			$payment_reserve_dao->commit_transaction();

			return array(static::$payment_reserve_id, $count);
		}
		catch (\Exception $e)
		{
			$payment_reserve_dao = new PaymentReserveDao();
			$payment_reserve_dao->rollback_transaction();
			throw new \Exception($e->getMessage());
		}
	}


	public static function reserveedit_data($id)
	{
		\Log::debug('[start]'. __METHOD__);

		static::$payment_reserve_id = $id;
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');

		$payment_reserve_dao = new \model\dao\paymentReserveDao();
		$payment_reserve_dao->start_transaction();

		// 親データを取得
		$arr_payment_reserve_detail = static::get_reservedetail($id);

		$obj_date_from_old = new \DateTime($arr_payment_reserve_detail['date_from']);
		$obj_date_to_old   = new \DateTime($arr_payment_reserve_detail['date_to']);
		$obj_date_from_new = new \DateTime(static::$date_from);
		$obj_date_to_new   = new \DateTime(static::$date_to);

		$payment_dao = new PaymentDao();

		// 対象期間が後ろに延びたとき新規登録
		if ($obj_date_to_new->getTimestamp() > $obj_date_to_old->getTimestamp())
		{
			$obj_date_to = new \DateTime($arr_payment_reserve_detail['date_to']);
			$from = \Date::forge($obj_date_to->getTimestamp() + 60 * 60 * 24)->format('%Y-%m-%d');
			$to   = static::$date_to;
			switch (static::$every_type)
			{
				case static::EVERY_TYPE_YEAR:
					$result = static::set_year_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_MONTH:
					$result = static::set_month_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_WEEK:
					$result = static::set_week_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_DAY:
					$result = static::set_day_reserveadd($datetime, $from, $to);
					break;
			}
		}
		// 対象期間が前に伸びたとき新規登録
		if ($obj_date_from_old->getTimestamp() > $obj_date_from_new->getTimestamp())
		{
			$obj_date_from = new \DateTime($arr_payment_reserve_detail['date_from']);
			$from = static::$date_from;
			$to   = \Date::forge($obj_date_from->getTimestamp() - 60 * 60 * 24)->format('%Y-%m-%d');
			switch (static::$every_type)
			{
				case static::EVERY_TYPE_YEAR:
					$result = static::set_year_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_MONTH:
					$result = static::set_month_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_WEEK:
					$result = static::set_week_reserveadd($datetime, $from, $to);
					break;
				case static::EVERY_TYPE_DAY:
					$result = static::set_day_reserveadd($datetime, $from, $to);
					break;
			}
		}
		// 対象期間が後ろに短くなった時は削除
		if ($obj_date_to_old->getTimestamp() > $obj_date_to_new->getTimestamp())
		{
			$from = \Date::forge($obj_date_to_new->getTimestamp() + 60 * 60 * 24)->format('%Y-%m-%d');
			$to   = \Date::forge($obj_date_to_old->getTimestamp())->format('%Y-%m-%d');
			$payment_dao->reserve_delete(static::$payment_reserve_id, $from, $to);
		}
		// 対象期間が前に短くなった時は削除
		if ($obj_date_from_new->getTimestamp() > $obj_date_from_old->getTimestamp())
		{
			$from = \Date::forge($obj_date_from_old->getTimestamp())->format('%Y-%m-%d');
			$to   = \Date::forge($obj_date_from_new->getTimestamp() - 60 * 60 * 24)->format('%Y-%m-%d');
			$payment_dao->reserve_delete(static::$payment_reserve_id, $from, $to);
		}

		// 親データを更新
		$result_main = static::update_payment_reserve($datetime, $id);

		// 子データを更新
		// 日付選択が変更なしの場合定期IDで既存データで更新
		// 日付選択が変更されているときは抽出したpaymentテーブルIDで個別に更新する
		switch (static::$every_type)
		{
			case static::EVERY_TYPE_YEAR:
				if ($arr_payment_reserve_detail['every_month_selected'] == static::$every_month_selected and
					$arr_payment_reserve_detail['every_day_selected'] == static::$every_day_selected)
				{
					$result = static::update_payment_by_reserve_id($datetime, static::$payment_reserve_id);
				}
				else
				{
					$result = static::update_payment_by_selected_id($datetime, static::$payment_reserve_id);
				}
				break;
			case static::EVERY_TYPE_MONTH:
				if ($arr_payment_reserve_detail['every_day_selected'] == static::$every_day_selected)
				{
					$result = static::update_payment_by_reserve_id($datetime, static::$payment_reserve_id);
				}
				else
				{
					$result = static::update_payment_by_selected_id($datetime, static::$payment_reserve_id);
				}
				break;
			case static::EVERY_TYPE_WEEK:
				if ($arr_payment_reserve_detail['every_dayofweek_selected'] == static::$every_dayofweek_selected)
				{
					$result = static::update_payment_by_reserve_id($datetime, static::$payment_reserve_id);
				}
				else
				{
					// 一旦削除
					$payment_dao->reserve_delete(static::$payment_reserve_id, static::$date_from, static::$date_to);
					$result = static::set_week_reserveadd($datetime);
				}
				break;
			case static::EVERY_TYPE_DAY:
				$result = static::update_payment_by_reserve_id($datetime, static::$payment_reserve_id);
				break;
		}

		$payment_reserve_dao->commit_transaction();

		return $result_main;
	}


	private static function update_payment_by_selected_id($datetime, $payment_reserve_id)
	{
		$payment_dao = new PaymentDao();
		$arr_list = $payment_dao->get(array('payment_reserve_id' => $payment_reserve_id, 'payment_reserve_status' => '0', 'date>=' => static::$date_from, 'date<=' => static::$date_to), array('id', 'date'));
		$result = array();
		foreach ($arr_list as $i => $val)
		{
			switch (static::$every_type)
			{
				case static::EVERY_TYPE_YEAR:
					$date = preg_replace('/([\d]+)\-([\d]+)$/', sprintf('%02d', static::$every_month_selected). '-'. sprintf('%02d', static::$every_day_selected), $val->date);
					$result = static::update_payment_reserve_for_year_month_day($date, $val->id, $datetime);
					break;
				case static::EVERY_TYPE_MONTH:
					$date = preg_replace('/[\d]+$/', sprintf('%02d', static::$every_day_selected), $val->date);
					$result = static::update_payment_reserve_for_year_month_day($date, $val->id, $datetime);
					break;
			}
		}

		return $result;
	}


	private static function update_payment_reserve($datetime, $id)
	{
		$year  = \Date::forge()->format('%Y');
		$month = ! empty(static::$every_month_selected)? static::$every_month_selected: \Date::forge()->format('%m');
		$day   = ! empty(static::$every_day_selected)? static::$every_day_selected: \Date::forge()->format('%d');
		if ((int)$month > 12 || (int)$day > 31)
		{
			throw new \Exception($year. "-". $month. "-". $day. "日付指定が不正です");
		}
		$payment_reserve_dao = new PaymentReserveDao();
		$arr_params = array(
				'user_id'   => static::$user_id,
				'fix_id'    => static::$fix_id,
				'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
				'name'      => static::$name,
				'detail'    => static::$detail,
				'shop'      => static::$shop,
				'date_from' => static::$date_from,
				'date_to'   => static::$date_to,
				'every_type' => static::$every_type,
				'every_month_selected'     => static::$every_month_selected,
				'every_day_selected'       => static::$every_day_selected,
				'cost'      => static::$cost,
				'remark'    => static::$remark,
				'work_side_per' => static::$work_side_per,
				'use_type'      => static::$use_type,
				'paymethod_id'  => static::$paymethod_id,
				'created_at'    => $datetime,
				'updated_at'    => $datetime,
		);

		if (isset(static::$every_dayofweek_selected) and static::$every_dayofweek_selected !== "")
		{
			$arr_params['every_dayofweek_selected'] = static::$every_dayofweek_selected;
		}

		$result = $payment_reserve_dao->update($arr_params, $id);

		return $result;
	}


	private static function update_payment_by_reserve_id($datetime, $reserve_id)
	{
		$arr_value = array(
				'fix_id' => static::$fix_id,
				'is_fix' => empty(static::$is_fix)? '0': static::$is_fix,
				'name'   => static::$name,
				'detail' => static::$detail,
				'shop'   => static::$shop,
				'cost'   => static::$cost,
				'remark' => static::$remark,
				'work_side_per' => static::$work_side_per,
				'use_type'      => static::$use_type,
				'paymethod_id'  => static::$paymethod_id,
				'updated_at'    => $datetime,
		);
		$payment_dao = new PaymentDao();
		$result = $payment_dao->update_by_reserve_id($arr_value, $reserve_id, static::$date_from, static::$date_to);

		return $result;
	}


	private static function update_payment_reserve_for_year_month_day($date, $payment_id, $datetime)
	{
		preg_match('/^([\d]*)-([\d]*)-([\d]*)$/', $date, $match);
		$year  = $match[1];
		$month = $match[2];
		$day   = $match[3];
		$is_date_enabled = false;
		while ($is_date_enabled == false)
		{
			if (checkdate($month, $day, $year))
			{
				$is_date_enabled = true;
				$date = $year. "-". $month. "-". $day;
			}
			else
			{
				$day = (int)$day - 1;
			}
		}
		$arr_value = array(
				'fix_id' => static::$fix_id,
				'is_fix' => empty(static::$is_fix)? '0': static::$is_fix,
				'name'   => static::$name,
				'detail' => static::$detail,
				'shop'   => static::$shop,
				'date'   => $date,
				'cost'   => static::$cost,
				'remark' => static::$remark,
				'work_side_per' => static::$work_side_per,
				'use_type'      => static::$use_type,
				'paymethod_id'  => static::$paymethod_id,
				'updated_at'    => $datetime,
		);
		$payment_dao = new PaymentDao();
		$result = $payment_dao->reserve_update($arr_value, $payment_id);

		return $result;
	}


	private static function set_year_reserveadd($datetime=null, $date_from=null, $date_to=null)
	{
		\Log::debug('[start]'. __METHOD__);

		$date_from = ! empty($date_from)? $date_from: static::$date_from;
		$date_to   = ! empty($date_to)? $date_to: static::$date_to;
		preg_match('/^[\d]{4}/', $date_from, $match_from);
		preg_match('/^[\d]{4}/', $date_to, $match_to);
		$year_from = $match_from[0];
		$year_to   = $match_to[0];
		if ($year_from > $year_to)
		{
			throw new \Exception("定期期間が不正 [". $date_from. " -> ". $date_to. "]");
		}

		$payment_dao = new PaymentDao();
		$datetime = ! empty($datetime)? $datetime: \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		for ($year=$year_from; $year<=$year_to; $year++)
		{
			$is_enabled_date = false;
			$date = "";
			$day  = static::$every_day_selected;
			while ( ! $is_enabled_date)
			{
				if (checkdate(static::$every_month_selected, $day, $year))
				{
					$is_enabled_date = true;
					$date = $year. "-". static::$every_month_selected. "-". $day;
					break;
				}
				else
				{
					$is_enabled_date = false;
					$day = (int)$day - 1;
					$day = sprintf('%02d', $day);
				}
			}

			$result = $payment_dao->create(array(
					'user_id'   => static::$user_id,
					'fix_id'    => static::$fix_id,
					'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
					'name'      => static::$name,
					'detail'    => static::$detail,
					'shop'      => static::$shop,
					'date'      => $date,
					'cost'      => static::$cost,
					'remark'    => static::$remark,
					'work_side_per' => static::$work_side_per,
					'use_type'      => static::$use_type,
					'paymethod_id'  => static::$paymethod_id,
					'payment_reserve_id'     => static::$payment_reserve_id,
					'payment_reserve_status' => '0',
					'created_at'    => $datetime,
					'updated_at'    => $datetime,
			));
		}
		return $result;
	}


	private static function set_month_reserveadd($datetime=null, $date_from=null, $date_to=null)
	{
		\Log::debug('[start]'. __METHOD__);

		$date_from = ! empty($date_from)? $date_from: static::$date_from;
		$date_to   = ! empty($date_to)? $date_to: static::$date_to;
		$obj_date_from = new \DateTime($date_from);
		$obj_date_to   = new \DateTime($date_to);
		$ts_from = $obj_date_from->getTimestamp();
		$ts_to   = $obj_date_to->getTimestamp();
		preg_match('/^([\d]{4})-([\d]+)-([\d]+$)/', $date_from, $match_from);
		preg_match('/^([\d]{4})-([\d]+)-([\d]+$)/', $date_to, $match_to);
		$year_from  = $match_from[1];
		$year_to    = $match_to[1];
		$month_from = $match_from[2];
		$month_to   = $match_to[2];
		$day_from   = $match_from[3];
		$day_to     = $match_to[3];

		if (intval($year_from. $month_from) > intval($year_to. $month_to))
		{
			throw new \Exception("定期期間[年]が不正 [". $date_from. " -> ". $date_to. "]");
		}

		$arr_ym = "";
		for($i=$year_from; $i<=$year_to; $i++)
		{
			$for_month_from = 1;
			$for_month_to   = 12;
			if ($i == $year_from)
			{
				$for_month_from = (int)$month_from;
			}
			if ($i == $year_to)
			{
				$for_month_to   = (int)$month_to;
			}

			for($j=$for_month_from; $j<=$for_month_to; $j++)
			{
				$ym = $i. sprintf('%02d', $j);
				$arr_ym[$ym] = $i. "-". sprintf('%02d', $j);
			}
		}
		$payment_dao = new PaymentDao();
		$datetime = ! empty($datetime)? $datetime: \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$result = array();

		foreach ($arr_ym as $i => $v)
		{
			preg_match('/^([\d]+)-([\d]+)$/', $v, $match);
			$year =  $match[1];
			$month = $match[2];
			$is_enabled_date = false;
			$date = "";
			$day  = static::$every_day_selected;
			while ( ! $is_enabled_date)
			{
				if (checkdate($month, $day, $year))
				{
					$is_enabled_date = true;
					$date = $year. "-". $month. "-". $day;
					break;
				}
				else
				{
					$is_enabled_date = false;
					$day = (int)$day - 1;
					$day = sprintf('%02d', $day);
				}
			} // endwhile

			$obj_date = new \DateTime($date);
			$ts = $obj_date->getTimestamp();
			\Log::info($ts_from. '>='. $ts. '<='. $ts_to);
			if ($ts >= $ts_from and $ts <= $ts_to)
			{
				$result = $payment_dao->create(array(
						'user_id'   => static::$user_id,
						'fix_id'    => static::$fix_id,
						'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
						'name'      => static::$name,
						'detail'    => static::$detail,
						'shop'      => static::$shop,
						'date'      => $date,
						'cost'      => static::$cost,
						'remark'    => static::$remark,
						'work_side_per' => static::$work_side_per,
						'use_type'      => static::$use_type,
						'paymethod_id'  => static::$paymethod_id,
						'payment_reserve_id'     => static::$payment_reserve_id,
						'payment_reserve_status' => '0',
						'created_at'    => $datetime,
						'updated_at'    => $datetime,
				));
			}
		}
		return $result;
	}


	private static function set_week_reserveadd($datetime=null, $date_from=null, $date_to=null)
	{
		\Log::debug('[start]'. __METHOD__);

		$date_from = ! empty($date_from)? $date_from: static::$date_from;
		$date_to   = ! empty($date_to)? $date_to: static::$date_to;

		$obj_datefrom = new \DateTime($date_from);
		$obj_dateto   = new \DateTime($date_to);
		$datefrom_ts = $obj_datefrom->getTimestamp();
		$dateto_ts   = $obj_dateto->getTimestamp();
		if ($datefrom_ts > $dateto_ts)
		{
			throw new \Exception("定期期間が不正 [". $date_from. " -> ". $date_to. "]");
		}

		$arr_ym = array();
		$arr_ym = \Date::range_to_array($datefrom_ts, $dateto_ts, "+1 days");

		$payment_dao = new PaymentDao();
		if (empty($datetime))
		{
			$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		}
		foreach ($arr_ym as $i => $val)
		{
			if (\Date::forge($val->get_timestamp())->format('%w') === static::$every_dayofweek_selected )
			{
				$result = $payment_dao->create(array(
						'user_id'   => static::$user_id,
						'fix_id'    => static::$fix_id,
						'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
						'name'      => static::$name,
						'detail'    => static::$detail,
						'shop'      => static::$shop,
						'date'      => \Date::forge($val->get_timestamp())->format('%Y-%m-%d'),
						'cost'      => static::$cost,
						'remark'    => static::$remark,
						'work_side_per' => static::$work_side_per,
						'use_type'      => static::$use_type,
						'paymethod_id'  => static::$paymethod_id,
						'payment_reserve_id'     => static::$payment_reserve_id,
						'payment_reserve_status' => '0',
						'created_at'    => $datetime,
						'updated_at'    => $datetime,
				));
			}
		}

		return $result;
	}


	private static function set_day_reserveadd($datetime=null, $date_from=null, $date_to=null)
	{
		\Log::debug('[start]'. __METHOD__);

		$date_from = ! empty($date_from)? $date_from: static::$date_from;
		$date_to   = ! empty($date_to)? $date_to: static::$date_to;

		$obj_datefrom = new \DateTime($date_from);
		$obj_dateto   = new \DateTime($date_to);
		$datefrom_ts = $obj_datefrom->getTimestamp();
		$dateto_ts   = $obj_dateto->getTimestamp();
		if ($datefrom_ts > $dateto_ts)
		{
			throw new \Exception("定期期間が不正 [". $date_from. " -> ". $date_to. "]");
		}

		$arr_ym = array();
		$arr_ym = \Date::range_to_array($datefrom_ts, $dateto_ts, "+1 days");

		$payment_dao = new PaymentDao();
		$datetime = ! empty($datetime)? $datetime: \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		foreach ($arr_ym as $i => $val)
		{
			$result = $payment_dao->create(array(
					'user_id'   => static::$user_id,
					'fix_id'    => static::$fix_id,
					'is_fix'    => empty(static::$is_fix)? '0': static::$is_fix,
					'name'      => static::$name,
					'detail'    => static::$detail,
					'shop'      => static::$shop,
					'date'      => \Date::forge($val->get_timestamp())->format('%Y-%m-%d'),
					'cost'      => static::$cost,
					'remark'    => static::$remark,
					'work_side_per' => static::$work_side_per,
					'use_type'      => static::$use_type,
					'paymethod_id'  => static::$paymethod_id,
					'payment_reserve_id'     => static::$payment_reserve_id,
					'payment_reserve_status' => '0',
					'created_at'    => $datetime,
					'updated_at'    => $datetime,
			));
		}
		return $result;
	}

	public static function edit_data($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$result = $payment_dao->update(array(
				'fix_id' => static::$fix_id,
				'is_fix' => empty(static::$is_fix)? '0': static::$is_fix,
				'name'   => static::$name,
				'detail' => static::$detail,
				'shop'   => static::$shop,
				'date'   => static::$date,
				'cost'   => static::$cost,
				'remark' => static::$remark,
				'work_side_per' => static::$work_side_per,
				'use_type'      => static::$use_type,
				'paymethod_id'  => static::$paymethod_id,
				'payment_reserve_status' => static::$payment_reserve_status,
				'updated_at'    => $datetime,
		), $id);
		return $result;
	}

	public static function set_default_data($user_id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$datetime = \Date::forge()->format('%Y-%m-%d %H:%M:%S');
		$result = $payment_dao->create(array(
				'user_id'   => $user_id,
				'fix_id'    => static::OTHER_FIX_ID,
				'is_fix'    => '0',
				'name'      => 'ソフトウェア代',
				'detail'    => '家計簿アプリ-ペイジャーナル-',
				'shop'      => 'ラウンドアバウト',
				'date'      => \Date::forge()->format('%Y-%m-%d'),
				'cost'      => 0,
				'remark'    => 'こちらはサンプルデータです。不要の場合は削除してください。',
				'work_side_per' => 100,
				'use_type'  => '2',
				'created_at' => $datetime,
				'updated_at' => $datetime,
		));
		return $result;
	}

	public static function remove_data($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$result = $payment_dao->delete($id);

		return $result;
	}

	public static function get_count()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		# データベース
		$payment_dao = new PaymentDao();
		# 件数取得
		$count = $payment_dao->get_count($arr_where);
		return $count;
	}




	public static function get_list()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		$arr_sort = array(
				'sort'      => static::$sort_by,
				'direction' => static::$direction,
		);
		# データベース
		$payment_dao = new PaymentDao();
		# リスト取得
		$list = $payment_dao->get_list($arr_where, static::$offset, static::$limit, $arr_sort);
		return $list;
	}


	public static function get_reservelist()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		if (static::$sort_by == "date")
		{
			static::$sort_by = "date_to";
		}
		$arr_sort = array(
				'sort'      => static::$sort_by,
				'direction' => static::$direction,
		);
		# データベース
		$payment_reserve_dao = new PaymentReserveDao();
		# リスト取得
		$list = $payment_reserve_dao->get_list($arr_where, static::$offset, static::$limit, $arr_sort);
		return $list;
	}


	public static function get_reservecount()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		# データベース
		$payment_reserve_dao = new PaymentReserveDao();
		# 件数取得
		$count = $payment_reserve_dao->get_count($arr_where);
		return $count;
	}


	public static function reserveremove_data($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_reserve_dao = new \model\dao\paymentReserveDao();
		$payment_reserve_dao->start_transaction();
		$result = $payment_reserve_dao->delete($id);

		$payment_dao = new PaymentDao();
		$payment_dao->reserve_delete($id);

		$payment_reserve_dao->commit_transaction();

		return $result;
	}


	public static function get_detail($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$detail = $payment_dao->get_detail($id);
		return $detail;
	}


	public static function get_reservedetail($id)
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_reserve_dao = new PaymentReserveDao();
		$detail = $payment_reserve_dao->get_detail($id);
		return $detail;
	}


	public static function get_sum_cost()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		# データベース
		$payment_dao = new PaymentDao();
		# 合計取得
		$sum = $payment_dao->sum_cost_list($arr_where);
		return $sum;
	}

	public static function get_cost_summary()
	{
		\Log::debug('[start]'. __METHOD__);

		$payment_dao = new PaymentDao();
		$arr_result = $payment_dao->get_monthly_cost_with_work_side_per(static::$user_id, static::$year, static::$month, static::$search);
		$all_cost = 0;
		$fix_cost = 0;
		$outof_fix_cost = 0;
		$work_side_cost = 0;
		foreach ($arr_result as $i => $val)
		{
			$all_cost = $all_cost + $val->cost;
			if ($val->is_fix == '1')
			{
				$fix_cost = $fix_cost + $val->cost;
			}
			else
			{
				$outof_fix_cost = $outof_fix_cost + $val->cost;
			}
			$work_side_cost = $work_side_cost + round($val->cost * $val->work_side_per/100);
		}
		return array(
				'all_cost'       => $all_cost,
				'fix_cost'       => $fix_cost,
				'outof_fix_cost' => $outof_fix_cost,
				'work_side_cost' => $work_side_cost,
		);
	}

	public static function get_fixper_data()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		# データベース
		$payment_dao = new PaymentDao();
		# リスト取得
		$list = $payment_dao->get_fixper($arr_where);
		return $list;
	}

	public static function get_use_type_data()
	{
		\Log::debug('[start]'. __METHOD__);

		$arr_where = array(
				'year'    => static::$year,
				'month'   => static::$month,
				'day'     => static::$day,
				'search'  => static::$search,
				'user_id' => static::$user_id,
		);
		# データベース
		$payment_dao = new PaymentDao();
		# リスト取得
		$list = $payment_dao->get_use_type($arr_where);
		return $list;
	}


	public static function get_cache_data()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			$average_fix_cost = \Cache::get('average_fix_cost')[static::$user_id];
			return array(
					'average_fix_cost' => $average_fix_cost,
			);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			return array(
					'average_fix_cost' => 0,
			);
		}
	}



}