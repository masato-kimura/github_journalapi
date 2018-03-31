<?php
use Fuel\Core\Controller_Rest;
use service\PaymentService;
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
class Controller_Payment extends \Controller_Rest
{
	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function post_list($only_list=false)
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_list();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# クラス変数へセット
			PaymentService::set_request();

			if ($only_list)
			{
				$arr_response = array(
						'success'  => true,
						'code'     => '1001',
						'response' => '',
						'result'   => array(
								'list' => PaymentService::get_list(),
						),
				);
				\Log::info($arr_response);
				\Log::info('[end]'. PHP_EOL. PHP_EOL);
				return $this->response($arr_response);
			}

			# DB集計データ取得
			$arr_cost_summary = PaymentService::get_cost_summary();

			# cacheデータ取得
			$arr_cache_data = PaymentService::get_cache_data();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array(
							'list'             => PaymentService::get_list(),          // 一覧
							'fix_per_list'     => PaymentService::get_fixper_data(),   // 固定費別集計
							'use_type_list'    => PaymentService::get_use_type_data(), // 使用目的別集計
							'all_cost'         => isset($arr_cost_summary['all_cost'])? $arr_cost_summary['all_cost']: '', // 対象月集計
							'fix_cost'         => isset($arr_cost_summary['fix_cost'])? $arr_cost_summary['fix_cost']: '',
							'outof_fix_cost'   => isset($arr_cost_summary['outof_fix_cost'])? $arr_cost_summary['outof_fix_cost']: '',
							'work_side_cost'   => isset($arr_cost_summary['work_side_cost'])? $arr_cost_summary['work_side_cost']: '',
							'average_fix_cost' => $arr_cache_data['average_fix_cost'],
					),
			);
			//\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$code = $e->getCode();
			$arr_response = array(
					'success' => false,
					'code'    => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result' => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	public function post_count()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_count();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# リクエストをメンバ変数にセット
			PaymentService::set_request();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('count' => PaymentService::get_count()),
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
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_detail($id)
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_detail($id);

			# リクエストをメンバ変数にセット
			PaymentService::set_request($id);

			# データベース操作
			$result = PaymentService::get_detail($id);

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('detail' => $result),
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
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			//\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	public function post_add()
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_add();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request();

			# データベース登録
			list($primary_key, $count) = PaymentService::add_data();
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('id' => $primary_key),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'success'  => false,
					'code'     => '9001',
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	public function post_edit($id)
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_edit($id);

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request($id);

			# DB操作
			$result = PaymentService::edit_data($id);
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('id' => $result),
			);
			// \Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$code = $e->getCode();
			$arr_response = array(
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}

	public function post_remove($id)
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_remove($id);

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request($id);

			# DB
			$result = PaymentService::remove_data($id);
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => $result,
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
					'success'  => false,
					'code'     =>  empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reservecount()
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reservecount();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# リクエストをメンバ変数にセット
			PaymentService::set_request();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('count' => PaymentService::get_reservecount()),
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
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reservelist()
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reservelist();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# クラス変数へセット
			PaymentService::set_request_for_reservelist();

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array(
							'list' => PaymentService::get_reservelist(),
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
					'success' => false,
					'code'    => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result' => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reservedetail($id)
	{
		try
		{
			\Log::debug('[start]'. __METHOD__);

			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reservedetail($id);

			# リクエストをメンバ変数にセット
			PaymentService::set_request($id);

			# データベース操作
			$result = PaymentService::get_reservedetail($id);

			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('detail' => $result),
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
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reserveadd()
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reserveadd();

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request();

			# データベース登録
			list($primary_key, $count) = PaymentService::reserveadd_data();
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('id' => $primary_key),
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			$arr_response = array(
					'success'  => false,
					'code'     => '9001',
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reserveedit($id)
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reserveedit($id);

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request($id);

			# DB操作
			$result = PaymentService::reserveedit_data($id);
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => array('id' => $result),
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
					'success'  => false,
					'code'     => empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}


	public function post_reserveremove($id)
	{
		try
		{
			# リクエストを取得
			PaymentService::get_json_request();

			# バリデーション
			PaymentService::validation_for_reserveremove($id);

			# ログイン状態チェック
			LoginService::set_request();
			LoginService::check_user_login_hash();

			# メンバ変数にセット
			PaymentService::set_request($id);

			# DB
			$result = PaymentService::reserveremove_data($id);
			$arr_response = array(
					'success'  => true,
					'code'     => '1001',
					'response' => '',
					'result'   => $result,
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
					'success'  => false,
					'code'     =>  empty($code)? '9001': $code,
					'response' => $e->getMessage(),
					'result'   => ''
			);
			\Log::info($arr_response);
			\Log::info('[end]'. PHP_EOL. PHP_EOL);
			return $this->response($arr_response);
		}
	}



}
