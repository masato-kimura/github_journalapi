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
final class Controller_User_Login extends \Controller_Rest
{
	public function post_index()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			LoginService::get_json_request();

			# バリデーションチェック
			LoginService::validation_for_login();

			# リクエストをメンバ変数にセット
			LoginService::set_request();

			# ログイン処理を実行
			if ( ! LoginService::set_transaction_for_login())
			{
				#error
				$arr_response = array(
						'success'  => false,
						'code'     => '7010',
						'response' => 'not registed user',
						'result'   => array(),
				);
			}
			else
			{
				$arr_user_info = LoginService::get_user_info_from_property();
				$arr_response = array(
						'success'  => true,
						'code'     => '1001',
						'response' => 'you login done !',
						'result'   => array(
								'user_id'         => $arr_user_info['user_id'],
								'user_name'       => $arr_user_info['user_name'],
								'oauth_type'      => $arr_user_info['oauth_type'],
								'oauth_id'        => $arr_user_info['oauth_id'],
								'login_hash'      => $arr_user_info['login_hash'],
								'email'           => $arr_user_info['email'],
								'password_digits' => $arr_user_info['password_digits'],
								'member_type'     => $arr_user_info['member_type'],
								'last_login'      => $arr_user_info['last_login'],
						),
				);
			}
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine().']');
			$code = $e->getCode();
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage()
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
			LoginService::get_json_request();

			# バリデーションチェック
			LoginService::validation_for_logincheck();

			# リクエストをメンバ変数にセット
			UserService::set_request();

			# メンバ変数をフォーマット
			UserService::format_for_logincheck();

			# ユーザ情報を取得
			$arr_regist_user = (array)UserService::is_regist();
			$arr_regist_user = array_filter($arr_regist_user);
			if (empty($arr_regist_user))
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