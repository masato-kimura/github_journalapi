<?php
namespace service;

interface LoginStrategy
{
	public function get_user_info(array $arr_user_info);
	public function login(array $arr_user_info);
	public function logout();
}