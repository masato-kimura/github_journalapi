<?php
use service\UserService;
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
final class Controller_User_Password extends \Controller_Rest
{
	/**
	 * oauth or email regist
	 */
	public function post_reissuerequest()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_password_reissue_request();

			# メンバ変数にリクエストをセット
			UserService::set_request();

			# データベースに登録
			UserService::transaction_for_password_reissue_request();

			# 再登録画面誘導メール送信
			UserService::send_mail_for_password_reissue();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'password reissue request done',
				'result' =>array(),
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
	public function post_reissuedone()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# JSONリクエストを取得
			UserService::get_json_request();

			# バリデーションチェック
			UserService::validation_for_password_reissue_done();

			# メンバ変数にリクエストをセット
			UserService::set_request();

			# データベースに登録
			UserService::transaction_for_password_reissue_done();

			# ユーザ情報を取得
			$arr_user_info = LoginService::get_user_info_from_property();

			# APIレスポンス
			$arr_response = array(
				'success' => true,
				'code'=> '1001',
				'response' => 'user password reissue done!',
				'result' =>array(
					'user_id'    => $arr_user_info['user_id'],
					'user_name'  => $arr_user_info['user_name'],
					'email'      => $arr_user_info['email'],
					'login_hash' => $arr_user_info['login_hash'],
					'oauth_type' => $arr_user_info['oauth_type'],
					'oauth_id'   => $arr_user_info['oauth_id'],
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
}

