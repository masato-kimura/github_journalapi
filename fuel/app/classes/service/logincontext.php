<?php
namespace service;

class LoginContext
{
	private $strategy;

	public function __construct(LoginStrategy $strategy)
	{
		$this->strategy = $strategy;
	}

	/**
	 * UserDtoにユーザ情報が入っていることが条件
	 *
	 */
	public function get_user_info(array $arr_user_info)
	{
		return $this->strategy->get_user_info($arr_user_info);
	}

	public function login(array $arr_user_info)
	{
		return $this->strategy->login($arr_user_info);
	}

	public function logout(array $arr_user_info)
	{
		return $this->strategy->logout($arr_user_info);
	}
}
