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
final class Controller_User_Edit extends \Controller_Rest
{
	public function post_index()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_edit();

			# メンバ変数にリクエストをセット
			UserService::set_request();
			LoginService::set_request();

			# データベースを更新
			UserService::transaction_for_edit_user_info();

			# 確定画面誘導メール送信（email更新時）
			UserService::send_mail_for_edit_decide();

			# 確定画面誘導メール送信（password更新時）
			UserService::send_mail_for_edit_password();

			# ユーザ情報を取得
			UserService::get_user_info_from_table_by_user_id();

			# ログイン情報を取得
			$arr_login_info = LoginService::get_user_info_from_property();
			$arr_user_info  = UserService::get_user_info_from_property();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'user edit complate!',
				'result' =>array(
					'user_id'            => $arr_user_info['user_id'],
					'user_name'          => $arr_user_info['user_name'],
					'login_hash'         => $arr_login_info['login_hash'],
					'email'              => $arr_user_info['email'],
					'password_digits'    => $arr_user_info['password_digits'],
					'is_password_change' => $arr_user_info['is_password_change'],
					'is_email_change'    => $arr_user_info['is_email_change'],
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

	public function post_editdecide()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_editdecide();

			# メンバ変数にリクエストをセット
			UserService::set_request();

			# データベースを更新
			UserService::transaction_for_set_edit_decide();

			# ログイン情報を取得
			$arr_user_info  = UserService::get_user_info_from_property();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'user edit complate!',
				'result' =>array(
					'user_id'     => $arr_user_info['user_id'],
					'user_name'   => $arr_user_info['user_name'],
					'login_hash'  => $arr_user_info['login_hash'],
					'decide_hash' => $arr_user_info['decide_hash'],
					'oauth_type'  => $arr_user_info['oauth_type'],
					'email'       => $arr_user_info['email'],
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
}