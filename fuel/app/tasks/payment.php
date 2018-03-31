<?php
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

namespace Fuel\Tasks;

use model\dao\PaymentDao;
use Fuel\Core\Cache;
use service\UserService;
/**
 * Robot example task
 *
 * Ruthlessly stolen from the beareded Canadian sexy symbol:
 *
 *		Derek Allard: http://derekallard.com/
 *
 * @package		Fuel
 * @version		1.0
 * @author		Phil Sturgeon
 */

class Payment
{
	public static function sum_fix()
	{
		\Log::debug('[start]'. __METHOD__);

		# 対象ユーザを取得
		$arr_users = UserService::get_users();
		$payment_dao = new PaymentDao();
		$average_fix_cost = array();

		foreach ($arr_users as $i => $user_id)
		{
			$sum_fix_cost = $payment_dao->sum_fix($user_id);
			$arr_date = $payment_dao->get_payment_date($user_id);
			$arr_yearmonth = array();
			foreach($arr_date as $i => $val)
			{
				if (preg_match('/^([\d]{4}\-[\d]{2})\-[\d]{2}/i', $val['date'], $match))
				{
					if (isset($match[1]))
					{
						$arr_yearmonth[$match[1]] = true;
					}
				}
			}
			$count = count($arr_yearmonth);
			$average_fix_cost[$user_id] = round($sum_fix_cost/$count);
			\Log::info('結果：'. $average_fix_cost[$user_id]);
		}

		Cache::set('average_fix_cost', $average_fix_cost);
		return true;
	}


	/**
	 * This method gets ran when a valid method name is not used in the command.
	 *
	 * Usage (from command line):
	 *
	 * php oil r robots
	 *
	 * or
	 *
	 * php oil r robots "Kill all Mice"
	 *
	 * @return string
	 */
	public static function run($speech = null)
	{
		if ( ! isset($speech))
		{
			$speech = 'KILL ALL HUMANS!';
		}

		$eye = \Cli::color("*", 'red');

		return \Cli::color("
					\"{$speech}\"
			          _____     /
			         /_____\\", 'blue')."\n"
.\Cli::color("			    ____[\\", 'blue').$eye.\Cli::color('---', 'blue').$eye.\Cli::color('/]____', 'blue')."\n"
.\Cli::color("			   /\\ #\\ \\_____/ /# /\\
			  /  \\# \\_.---._/ #/  \\
			 /   /|\\  |   |  /|\\   \\
			/___/ | | |   | | | \\___\\
			|  |  | | |---| | |  |  |
			|__|  \\_| |_#_| |_/  |__|
			//\\\\  <\\ _//^\\\\_ />  //\\\\
			\\||/  |\\//// \\\\\\\\/|  \\||/
			      |   |   |   |
			      |---|   |---|
			      |---|   |---|
			      |   |   |   |
			      |___|   |___|
			      /   \\   /   \\
			     |_____| |_____|
			     |HHHHH| |HHHHH|", 'blue');
	}

	/**
	 * An example method that is here just to show the various uses of tasks.
	 *
	 * Usage (from command line):
	 *
	 * php oil r robots:protect
	 *
	 * @return string
	 */
	public static function protect()
	{
		$eye = \Cli::color("*", 'green');

		return \Cli::color("
					\"PROTECT ALL HUMANS\"
			          _____     /
			         /_____\\", 'blue')."\n"
.\Cli::color("			    ____[\\", 'blue').$eye.\Cli::color('---', 'blue').$eye.\Cli::color('/]____', 'blue')."\n"
.\Cli::color("			   /\\ #\\ \\_____/ /# /\\
			  /  \\# \\_.---._/ #/  \\
			 /   /|\\  |   |  /|\\   \\
			/___/ | | |   | | | \\___\\
			|  |  | | |---| | |  |  |
			|__|  \\_| |_#_| |_/  |__|
			//\\\\  <\\ _//^\\\\_ />  //\\\\
			\\||/  |\\//// \\\\\\\\/|  \\||/
			      |   |   |   |
			      |---|   |---|
			      |---|   |---|
			      |   |   |   |
			      |___|   |___|
			      /   \\   /   \\
			     |_____| |_____|
			     |HHHHH| |HHHHH|", 'blue');

	}
}

/* End of file tasks/robots.php */
