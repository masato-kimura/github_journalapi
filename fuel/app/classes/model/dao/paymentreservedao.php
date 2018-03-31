<?php
namespace model\dao;

class paymentReserveDao extends MySqlDao
{
	protected $_table_name;

	public function get_list(array $arr_where, $offset=0, $limit=100, array $arr_sort)
	{
		try
		{
			$search = isset($arr_where['search'])? $arr_where['search']: '';
			$year   = isset($arr_where['year'])? $arr_where['year']: '';
			$month  = isset($arr_where['month'])? $arr_where['month']: '';
			$day    = isset($arr_where['day'])? $arr_where['day']: '';
			$sort   = isset($arr_sort['sort'])? $arr_sort['sort']: 'date_from';
			$direction = isset($arr_sort['direction'])? $arr_sort['direction']: 'DESC';

			if (empty($year))
			{
				$year = \Date::forge()->format('%Y');
			}

			$from = $year. '-01-01';
			$to   = $year. '-12-31';

			$arr_columns = array(
				array('p.id', 'id'),
				array('p.user_id', 'user_id'),
				array('p.fix_id', 'fix_id'),
				array('p.is_fix', 'is_fix'),
				array('f.is_disp', 'is_disp'),
				array('f.to_aggre', 'to_aggre'),
				array(\DB::expr('ifnull(f.name, p.name)'), 'name'),
				array('p.detail', 'detail'),
				array('p.date_from', 'date_from'),
				array('p.date_to', 'date_to'),
				array('p.shop', 'shop'),
				array('p.cost', 'cost'),
				array('p.remark', 'remark'),
				array('p.work_side_per', 'work_side_per'),
				array('p.use_type', 'use_type'),
				array('p.paymethod_id', 'paymethod_id'),
				array('p.every_type', 'every_type'),
				array('p.every_month_selected', 'every_month_selected'),
				array('p.every_day_selected', 'every_day_selected'),
				array('p.every_dayofweek_selected', 'every_dayofweek_selected'),
				array('p.created_at', 'created_at'),
				array('p.updated_at', 'updated_at'),
			);
			$query = \DB::select_array($arr_columns);
			$query->from(array('payment_reserve', 'p'));
			$query->join(array('fix', 'f'), 'left');
			$query->on('p.fix_id', '=', 'f.id');
			$query->on('f.is_deleted', '=', \DB::expr('0'));
			$query->where('p.is_deleted', '=', '0');
			$query->where('p.user_id', '=', $arr_where['user_id']);
			//$query->where('p.date_to', '>=', $from);
			//$query->where('p.date_to', '<=', $to);
			$query->and_where_open();
			$query->or_where('f.to_aggre', '=', null);
			$query->or_where('f.to_aggre', '=', '1');
			$query->and_where_close();
			if ( ! empty($search))
			{
				$search = '%'. $search. '%';
				$query->and_where_open();
				$query->or_where('p.name', 'like', $search);
				$query->or_where('f.name', 'like', $search);
				$query->or_where('p.detail', 'like', $search);
				$query->or_where('p.shop', 'like', $search);
				$query->or_where('p.remark', 'like', $search);
				$query->and_where_close();
			}
			$query->order_by($sort, $direction);
			if ($sort == 'every_type')
			{
				//$query->order_by('every_month_selected', $direction);
				$query->order_by('every_day_selected', $direction);
				//$query->order_by('every_dayofweek_selected', $direction);
			}
			$query->order_by('id', 'DESC');
			$query->offset($offset);
			$query->limit($limit);
			return $query->execute()->as_array();
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}

	public function get_fixper(array $arr_where)
	{
		try
		{
			$search = isset($arr_where['search'])? $arr_where['search']: '';
			$year   = isset($arr_where['year'])? $arr_where['year']: '';
			$month  = isset($arr_where['month'])? $arr_where['month']: '';
			$day    = isset($arr_where['day'])? $arr_where['day']: '';

			if (empty($year))
			{
				$year = \Date::forge()->format('%Y');
			}
			if (empty($month))
			{
				$month = \Date::forge()->format('%m');
				$from = $year. '-01-01';
				$to   = $year. '-12-31';
			}
			else
			{
				if (empty($day))
				{
					$day_from = '01';
					$day_to   = '31';
				}
				else
				{
					$day_from = sprintf('%02d', $day);
					$day_to   = sprintf('%02d', $day);
				}
				$from = $year. '-'. $month. '-'. $day_from;
				$to   = $year. '-'. $month. '-'. $day_to;
			}

			$arr_columns = array(
					array('p.fix_id', 'fix_id'),
					array('f.name', 'fix_name'),
					array(\DB::expr('sum(p.cost)'), 'cost'),
			);
			$query = \DB::select_array($arr_columns);
			$query->from(array('payment_reserve', 'p'));
			$query->join(array('fix', 'f'), 'left');
			$query->on('p.fix_id', '=', 'f.id');
			$query->on('f.is_deleted', '=', \DB::expr('0'));
			$query->where('p.is_deleted', '=', '0');
			$query->where('p.user_id', '=', $arr_where['user_id']);
			$query->where('p.date', '>=', $from);
			$query->where('p.date', '<=', $to);
			$query->and_where_open();
			$query->or_where('f.to_aggre', '=', null);
			$query->or_where('f.to_aggre', '=', '1');
			$query->and_where_close();
			if ( ! empty($search))
			{
				$search = '%'. $search. '%';
				$query->and_where_open();
				$query->or_where('p.name', 'like', $search);
				$query->or_where('f.name', 'like', $search);
				$query->or_where('p.detail', 'like', $search);
				$query->or_where('p.shop', 'like', $search);
				$query->or_where('p.remark', 'like', $search);
				$query->and_where_close();
			}
			$query->group_by(array('p.fix_id'));
			$query->order_by('cost', 'desc');
			return $query->execute()->as_array();
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}

	public function get_use_type(array $arr_where)
	{
		try
		{
			$search = isset($arr_where['search'])? $arr_where['search']: '';
			$year   = isset($arr_where['year'])? $arr_where['year']: '';
			$month  = isset($arr_where['month'])? $arr_where['month']: '';
			$day    = isset($arr_where['day'])? $arr_where['day']: '';

			if (empty($year))
			{
				$year = \Date::forge()->format('%Y');
			}
			if (empty($month))
			{
				$month = \Date::forge()->format('%m');
				$from = $year. '-01-01';
				$to   = $year. '-12-31';
			}
			else
			{
				if (empty($day))
				{
					$day_from = '01';
					$day_to   = '31';
				}
				else
				{
					$day_from = sprintf('%02d', $day);
					$day_to   = sprintf('%02d', $day);
				}
				$from = $year. '-'. $month. '-'. $day_from;
				$to   = $year. '-'. $month. '-'. $day_to;
			}

			$arr_columns = array(
					array('p.use_type', 'use_type'),
					array(\DB::expr('sum(p.cost)'), 'cost'),
			);
			$query = \DB::select_array($arr_columns);
			$query->from(array('payment_reserve', 'p'));
			$query->join(array('fix', 'f'), 'left');
			$query->on('p.fix_id', '=', 'f.id');
			$query->on('f.is_deleted', '=', \DB::expr('0'));
			$query->where('p.is_deleted', '=', '0');
			$query->where('p.user_id', '=', $arr_where['user_id']);
			$query->where('p.date_to', '>=', $from);
			$query->where('p.date_to', '<=', $to);
			$query->and_where_open();
			$query->or_where('f.to_aggre', '=', null);
			$query->or_where('f.to_aggre', '=', '1');
			$query->and_where_close();
			if ( ! empty($search))
			{
				$search = '%'. $search. '%';
				$query->and_where_open();
				$query->or_where('p.name', 'like', $search);
				$query->or_where('p.detail', 'like', $search);
				$query->or_where('p.shop', 'like', $search);
				$query->or_where('p.remark', 'like', $search);
				$query->and_where_close();
			}

			$query->group_by(array('p.use_type'));
			$query->order_by('p.use_type', 'asc');
			return $query->execute()->as_array();
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}

	public function sum_cost_list($arr_where)
	{
		try
		{
			$search = isset($arr_where['search'])? $arr_where['search']: '';
			$year   = isset($arr_where['year'])? $arr_where['year']: '';
			$month  = isset($arr_where['month'])? $arr_where['month']: '';
			$day    = isset($arr_where['day'])? $arr_where['day']: '';
			if (empty($year))
			{
				$year = \Date::forge()->format('%Y');
			}
			if (empty($month))
			{
				$from = $year. '-01-01';
				$to   = $year. '-12-31';
			}
			else
			{
				if (empty($day))
				{
					$day_from = '01';
					$day_to   = '31';
				}
				else
				{
					$day_from = sprintf('%02d', $day);
					$day_to   = sprintf('%02d', $day);
				}
				$from = $year. '-'. $month. '-'. $day_from;
				$to   = $year. '-'. $month. '-'. $day_to;
			}

			$query = \DB::select(array(\DB::expr('sum(p.cost)'), 'sum_cost'));
			$query->from(array('payment_reserve', 'p'));
			$query->join(array('fix', 'f'), 'left');
			$query->on('p.fix_id', '=', 'f.id');
			$query->on('f.is_deleted', '=', \DB::expr('0'));

			$query->where('p.is_deleted', '=', '0');
			$query->where('p.user_id', '=', $arr_where['user_id']);
			$query->where('p.date', '>=', $from);
			$query->where('p.date', '<=', $to);
			$query->and_where_open();
			$query->or_where('f.to_aggre', '=', null);
			$query->or_where('f.to_aggre', '=', '1');
			$query->and_where_close();
			if ( ! empty($search))
			{
				$search = '%'. $search. '%';
				$query->and_where_open();
				$query->or_where('p.name', 'like', $search);
				$query->or_where('p.detail', 'like', $search);
				$query->or_where('p.shop', 'like', $search);
				$query->or_where('p.remark', 'like', $search);
				$query->or_where('f.name', 'like', $search);
				$query->and_where_close();
			}
			$result = $query->execute()->current();
			return $result['sum_cost'];
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}


	public function get_count($arr_where)
	{
		try
		{
			$search = isset($arr_where['search'])? $arr_where['search']: '';
			$year   = isset($arr_where['year'])? $arr_where['year']: '';
			$month  = isset($arr_where['month'])? $arr_where['month']: '';
			$day    = isset($arr_where['day'])? $arr_where['day']: '';

			if (empty($year))
			{
				$year = \Date::forge()->format('%Y');
			}
			$from = $year. '-01-01';
			$to   = $year. '-12-31';

			$query = \DB::select(array(\DB::expr('count(*)'), 'count'));
			$query->from(array('payment_reserve', 'p'));
			$query->join(array('fix', 'f'), 'left');
			$query->on('p.fix_id', '=', 'f.id');
			$query->on('f.is_deleted', '=', \DB::expr('0'));
			$query->where('p.user_id', '=', $arr_where['user_id']);
			$query->where('p.is_deleted', '=', '0');
			//$query->where('p.date_to', '>=', $from);
			//$query->where('p.date_to', '<=', $to);
			$query->and_where_open();
			$query->or_where('f.to_aggre', '=', null);
			$query->or_where('f.to_aggre', '=', '1');
			$query->and_where_close();
			if ( ! empty($search))
			{
				$search = '%'. $search. '%';
				$query->and_where_open();
				$query->or_where('p.name', 'like', $search);
				$query->or_where('f.name', 'like', $search);
				$query->or_where('p.detail', 'like', $search);
				$query->or_where('p.shop', 'like', $search);
				$query->or_where('p.remark', 'like', $search);
				$query->and_where_close();
			}

			$arr_result = $query->execute()->current();
			return $arr_result['count'];
		}
		catch (\Exception $e)
		{
			\Log::error($e->getMessage());
			\Log::error($e->getFile(). '['. $e->getLine(). ']');
			exit;
		}
	}

	public function get_detail($id)
	{
		$query = \DB::select_array();
		$query->from('payment_reserve');
		$query->where('id', '=', $id);
		$query->where('is_deleted', '=', '0');
		return $query->execute()->current();
	}

	public function sum_fix()
	{
		$query = \DB::select(array(\DB::expr('sum(p.cost)'), 'sum'));
		$query->from(array('payment_reserve', 'p'));
		$query->join(array('fix', 'f'), 'left');
		$query->on('p.fix_id', '=', 'f.id');
		$query->on('f.is_deleted', '=', \DB::expr('0'));
		$query->where('p.is_fix', '=', '1');
		$query->where('p.is_deleted', '=', '0');
		$query->where('p.date', '<', \Date::forge()->format('%Y-%m-01'));
		$query->and_where_open();
		$query->or_where('f.to_aggre', '=', null);
		$query->or_where('f.to_aggre', '=', '1');
		$query->and_where_close();
		$result = $query->execute()->current();
		return $result['sum'];
	}

	public function get_payment_date()
	{
		$query = \DB::select(array(\DB::expr('p.date'), 'date'));
		$query->from(array('payment_reserve', 'p'));
		$query->join(array('fix', 'f'), 'left');
		$query->on('p.fix_id', '=', 'f.id');
		$query->where('p.is_fix', '=', '1');
		$query->where('p.is_deleted', '=', '0');
		$query->where('p.date', '<', \Date::forge()->format('%Y-%m-01'));
		$query->and_where_open();
		$query->or_where('f.to_aggre', '=', null);
		$query->or_where('f.to_aggre', '=', '1');
		$query->and_where_close();
		$query->group_by('p.date');
		$query->order_by('p.date');
		return $query->execute()->as_array();
	}

	public function get_monthly_cost_with_work_side_per($user_id, $year, $month, $search=null)
	{
		if (empty($month))
		{
			$from = $year. '-01-01';
			$to   = $year. '-12-31';
		}
		else
		{
			$from = $year. '-'. $month. '-01';
			$to   = $year. '-'. $month. '-31';
		}

		$arr_columns = array(
				'p.cost',
				'p.is_fix',
				'p.work_side_per'
		);
		$query = \DB::select_array($arr_columns);
		$query->from(array('payment_reserve', 'p'));
		$query->join(array('fix', 'f'), 'left');
		$query->on('p.fix_id', '=', 'f.id');
		$query->where('p.is_deleted', '=', '0');
		$query->where('p.user_id', '=', $user_id);
		$query->where('p.date', '>=', $from);
		$query->where('p.date', '<=', $to);
		$query->and_where_open();
		$query->or_where('f.to_aggre', '=', null);
		$query->or_where('f.to_aggre', '=', '1');
		$query->and_where_close();
		if ( ! empty($search))
		{
			$search = '%'. $search. '%';
			$query->and_where_open();
			$query->or_where('p.name', 'like', $search);
			$query->or_where('f.name', 'like', $search);
			$query->or_where('p.detail', 'like', $search);
			$query->or_where('p.shop', 'like', $search);
			$query->or_where('p.remark', 'like', $search);
			$query->and_where_close();
		}

		$result = $query->as_object()->execute()->as_array();
		return $result;
	}


	public function create($arr_values)
	{
		$query = \DB::insert('payment_reserve');
		$query->set($arr_values);
		return $query->execute();
	}

	public function update($arr_values, $id)
	{
		$query = \DB::update('payment_reserve');
		$query->set($arr_values);
		$query->where('id', '=', $id);
		$query->where('is_deleted', '=', '0');
		return $query->execute();
	}

	public function delete($id)
	{
		$query = \DB::delete('payment_reserve');
		$query->where('id', '=', $id);
		return $query->execute();
	}
}
