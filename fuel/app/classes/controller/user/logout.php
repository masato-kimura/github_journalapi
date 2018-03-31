<?php

use service\LoginService;
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
final class Controller_User_Logout extends \Controller_Rest
{
	/**
	 * ログアウト処理
	 * 必須項目 user_id, login_hash, api_key
	 */
	public function post_index()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			LoginService::get_json_request();

			# バリデーションチェック
			LoginService::validation_for_logout();

			#メンバ変数にリクエストをセット
			LoginService::set_request();

			# ログアウト処理
			LoginService::set_logout();

			# ログアウト情報を取得
			$arr_login_info = LoginService::get_user_info_from_property();

			$arr_response = array(
				'success'  => true,
				'code'     => '1001',
				'response' => 'see you again !',
				'result'   => array(
					'user_id' => $arr_login_info['user_id'],
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
					'result'   => '',
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage()
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}
}