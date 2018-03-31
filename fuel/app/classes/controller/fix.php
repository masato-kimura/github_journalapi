<?php
use Fuel\Core\Controller_Rest;
use service\FixService;
use service\LoginService;

/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Fix extends \Controller_Rest
{
	public function action_index()
	{
		\Log::error(__METHOD__);
		var_dump(__METHOD__);exit;
	}

	public function post_list()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			// リクエストを取得
			FixService::get_json_request();

			// バリデーション
			FixService::validation_for_list();

			// ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			// DBからデータ取得
			FixService::set_request();
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('list' => FixService::get_list()),
			);
			\Log::info($arr_response);
			\Log::debug('[end]'. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => $e->getCode(),
					'response' => $e->getMessage(),
			);
			$this->response($arr_response);
			\Log::debug('[end]'. PHP_EOL);
			return false;
		}
	}

	public function post_add()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			// リクエストを取得
			FixService::get_json_request();

			// バリデーション
			FixService::validation_for_add();

			// ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			// DBへ登録
			FixService::set_request();
			list($id, $count) = FixService::add_data();
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('id' => $id),
			);
			\Log::debug('[end]'. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => $e->getCode(),
					'response' => $e->getMessage(),
			);
			$this->response($arr_response);
			\Log::debug('[end]'. PHP_EOL);
			return false;
		}
	}

	public function post_edit()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			// リクエストを取得
			FixService::get_json_request();

			// バリデーション
			FixService::validation_for_edit();

			// ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			// DB更新
			FixService::set_request();
			$return = FixService::edit_data();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array(),
			);
			\Log::debug('[end]'. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => $e->getCode(),
					'response' => $e->getMessage(),
			);
			$this->response($arr_response);
			\Log::debug('[end]'. PHP_EOL);
			return false;
		}
	}

	public function post_remove()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			// リクエストを取得
			FixService::get_json_request();

			// バリデーション
			FixService::validation_for_remove();

			// ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			// DB更新
			FixService::set_request();
			$return = FixService::remove_data();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array(),
			);
			\Log::debug('[end]'. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => $e->getCode(),
					'response' => $e->getMessage(),
			);
			$this->response($arr_response);
			\Log::debug('[end]'. PHP_EOL);
			return false;
		}
	}

	public function post_sort()
	{
		try
		{
			// リクエストを取得
			FixService::get_json_request();

			// バリデーション
			FixService::validation_for_sort();

			// ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			// DBへ反映
			FixService::set_request();
			FixService::set_sorted();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('list' => FixService::get_list()),
			);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'result'   => null,
					'success'  => false,
					'code'     => $e->getCode(),
					'response' => $e->getMessage(),
			);
			$this->response($arr_response);
			return false;
		}
	}


	/**
	 * The 404 action for the application.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_404()
	{
		return Response::forge(Presenter::forge('welcome/404'), 404);
	}
}
