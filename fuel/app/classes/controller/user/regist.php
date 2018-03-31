<?php

use service\LoginService;
use service\UserService;
/**
 * @throws \Exception
 *  1001 正常
 *  8001 システムエラー
 *  8002 DBエラー
 *  9001 認証エラー
 *  9002 リクエスト内容未存在
 *  9003 必須項目エラー
 * @author masato
 * @params httpリクエスト email, password, api_key, auth_type
 *
 */
final class Controller_User_Regist extends \Controller_Rest
{
	/**
	 * oauth or email regist
	 */
	public function post_index()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_regist();

			# メンバ変数にリクエストをセット
			UserService::set_request();

			# データベースに登録
			UserService::transaction_for_set_user_info();

			# 確定画面誘導メール送信（email新規時）
			UserService::send_mail_for_regist_decide();

			# ログイン情報を取得
			$arr_login_info = LoginService::get_user_info_from_property();
			$arr_user_info  = UserService::get_user_info_from_property();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'user first_regist complate!',
				'result' =>array(
					'user_id'     => $arr_login_info['user_id'],
					'user_name'   => $arr_login_info['user_name'],
					'login_hash'  => $arr_login_info['login_hash'],
					'is_decided'  => $arr_user_info['is_decided'],
					'decide_hash' => $arr_user_info['decide_hash'],
				),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$code = $e->getCode();
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	/**
	 * oauth or email regist
	 */
	public function post_decide()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_registdecide();

			# メンバ変数にリクエストをセット
			UserService::set_request();

			# データベースに登録
			UserService::transaction_for_set_registdecide();

			# ログイン情報を取得
			$arr_user_info  = UserService::get_user_info_from_property();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'regist decide complate!',
				'result' =>array(
					'user_id'     => $arr_user_info['user_id'],
					'user_name'   => $arr_user_info['user_name'],
					'login_hash'  => $arr_user_info['login_hash'],
					'is_decided'  => $arr_user_info['is_decided'],
					'decide_hash' => $arr_user_info['decide_hash'],
					'oauth_type'  => $arr_user_info['oauth_type'],
					'oauth_id'    => $arr_user_info['oauth_id'],
					'email'       => $arr_user_info['email'],
					'password_digits' => $arr_user_info['password_digits'],
				),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$code = $e->getCode();
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	public function post_check()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_registcheck();

			# リクエストをメンバ変数にセット
			UserService::set_request();

			# ユーザ登録済みかをチェック
			$arr_regist_user = (array)UserService::is_regist();
			$arr_regist_user = array_filter($arr_regist_user);

			if ( ! $arr_regist_user)
			{
				$arr_response = array(
						'success'  => true,
						'code'     => '9001',
						'response' => 'not registed user',
						'result'   => false,
				);
			}
			else
			{
				$arr_response = array(
						'success'  => true,
						'code'     => '1001',
						'response' => 'you login done !',
						'result'   => $arr_regist_user,
				);
			}
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$code = $e->getCode();
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage());
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}
}