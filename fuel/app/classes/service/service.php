<?php
namespace service;

class Service
{
	protected static $_obj_request = null;

	public static function start_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$dao = new MySqlDao();

		return $dao->start_transaction();
	}


	public static function commit_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$dao = new MySqlDao();

		return $dao->commit_transaction();
	}


	public static function rollback_transaction()
	{
		\Log::debug('[start]'. __METHOD__);

		$dao = new MySqlDao();

		return $dao->rollback_transaction();
	}


	/**
	 * JSONリクエストをphp:://inputから取得
	 * stdClassに変換しメンバ変数にセット
	 */
	public static function get_json_request()
	{
		\Log::debug('[start]'. __METHOD__);

		# リクエストを取得
		$handle = fopen('php://input', 'r');
		$json_request = fgets($handle);
		fclose($handle);
		static::$_obj_request = json_decode($json_request);
		\Log::info(static::$_obj_request);

		return true;
	}


	/**
	 * # バリデーションで利用するためobj_requestの値を$_POSTにセットする
	 * @param unknown $obj_request
	 * @return boolean
	 */
	protected static function _set_request_to_post($obj_request)
	{
		\Log::debug('[start]'. __METHOD__);

		foreach ($obj_request as $i => $val)
		{
			$_POST[$i] = $val;
		}

		return true;
	}

	protected static function _validate_base($validate)
	{
		\Log::debug('[start]'. __METHOD__);

		$validate->add_callable('AddValidation'); // fuel/app/classes/addvalidation.php

		# API認証キーを確認
		$validate->add('api_key', 'APIキー')
			->add_rule('required')
			->add_rule('check_api_key');

		return true;
	}

	protected static function _validate_run($validate, array $arr_value_params=null)
	{
		\Log::debug('[start]'. __METHOD__);

		if ( ! $validate->run($arr_value_params))
		{
			foreach ($validate->error() as $error)
			{
				\Log::error($error->get_message());
			}

			throw new \Exception('validate error');
		}

		return true;
	}
}