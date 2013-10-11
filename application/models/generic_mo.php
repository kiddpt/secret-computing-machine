<?php
class Generic_MO extends CI_Model {
	
	function __construct()
	{
		parent::__construct();
	}

	function get_all($table, $order_by = NULL, $type = NULL)
	{
		if($order_by)
			$this->db->order_by($order_by, $type);
		$query = $this->db->get($table);



		return $query->result();
	}
	
	function get_count($table,$filter)
	{
		$this->db->where($filter);

		return $this->db->count_all_results($table);
	}

	function get_where($table, $filter,$offset = null, $limit = null)
	{
		$query = $this->db->get_where($table,$filter,$limit,$offset);
		if($query)
			return $query->result();
		else
			return null;
	}

	function row_exist($table, $filter,$offset = NULL, $limit = NULL)
	{
		
		$this->db->flush_cache();

		// log_message('error',json_encode($filter));
		$this->db->where($filter);
		$this->db->from($table);

		$count = $this->db->count_all_results();

		// var_dump($count);
		// log_message('error',$this->db->last_query());
		
		// log_message('error',"COUNT : $count --------- ".  $this->db->count_all_results());
		if($count > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
		
// 		$query = $this->db->get_where($table,$offset);
		
// 		return $query->num_rows();
	}

	function get_row($table, $filter, $col = NULL)
	{
		$this->db->where($filter);
		$query = $this->db->get_where($table);

		$row = $query->row();

		if($col)
		{
			if($row->$col)
				return $row->$col;
		}
		else
		{
			return $row;
		}
	}

	function get_some_entries($table, $filter = NULL, $offset = 0, $limit = 10)
	{
		$this->db->offset($offset);
		$this->db->limit($limit);
		$this->db->filter($filter);
			
		$query = $this->db->get_where($table);
		return $query->result();
	}

	function get_sql($sql, $offset = 0, $limit = NULL)
	{
		if($limit)
			$sql = $sql. " LIMIT $offset, $limit";

		$query = $this->db->query($sql);

		if(method_exists($query,'result'))
			return $query->result();

		return NULL;
	}

	function insert_entry($table, $data)
	{
		$data = (!is_array($data))? (array) $data: $data;
			
		$data['created_by'] 	= ($this->userdata->user_id)? $this->userdata->user_id : 0;
		$data['date_created'] 	= ($data['date_created'])? $data['date_created'] : date('Y-m-d H:i:s');
			
		$this->db->insert($table, $data);

		return $this->db->insert_id();
	}

	function insert_entry_2($table, $data)
	{
		$data = (!is_array($data))? (array) $data: $data;
			
		$data['date_created'] 	= date('Y-m-d H:i:s');
			
		$this->db->insert($table, $data);

		return $this->db->insert_id();
	}

	function insert_batch_entry($table, $data)
	{
		$data['created_by'] 	=  $this->userdata->user_id;
		$data['date_created'] 	= date('Y-m-d H:i:s');

		$this->db->insert_batch($table, $data);
	}

	function update_entry($table, $data, $filter)
	{
		$data['updated_by'] 	= (int)  $this->userdata->user_id;

			
		// echo "GE_MO DATE_UPDATED:".date('Y-m-d H:i:s');
		$data['date_updated'] 	= date('Y-m-d H:i:s');
			
		// echo "$table " . json_encode($data) . " <BR>";
			
		return $this->db->update($table, $data, $filter);
	}

	function delete_entry($table, $filter)
	{
		return $this->db->delete($table, $filter);
	}

	function empty_table($table)
	{
		return $this->db->empty_table($table);
	}

	function get_country($id)
	{
		return $this->get_row('country', array('country_id' => $id));
	}

	function search_country($query)
	{
		$sql = "SELECT country,iso from country
				where LOWER(country) LIKE '$query%'";
			
			
		$data['countries'] = $this->get_sql($sql);
		
		
		return $data;
	}
	
	function get_country_landing($iso,$currency = "EUR")
	{
		$sql = "SELECT *, (o.price * rate) online_price  
				 from country c
				left join currency cu on cu.code = '$currency'
				left join operator o on o.country_id = c.iso
				where iso = '$iso'";
	
	
		$result = $this->get_sql($sql);
		$data['country'] = $result[0];
	
		$sql = "SELECT *
		from country c
		left join operator o on o.country_id = c.iso and `default` <> 1
		
		where iso = '$iso'";
	
		
	
		$data['operators'] = $this->get_sql($sql);
	
		return $data;
	}
	

	function get_countries()
	{
		return $this->get_all('country');

	}

	function get_reverts()
	{
		$sql = "SELECT *, u.email executed_by, u2.email reverted_by, r.date_created, r.date_updated
		FROM revert r
		LEFT JOIN user u on u.user_id = r.created_by
		LEFT JOIN user u2 on u2.user_id = r.updated_by
		";
		return $this->get_sql($sql);

	}

	function execute_revert($revert_id)
	{
		$data =  $this->get_where('revert', array('revert_id' => $revert_id));
		$data = $data[0];
		//    	echo $data->revert_action;
		$this->get_sql($data->revert_action);
			
		$this->update_entry('revert', array('date_reverted' => date('y-m-d H:i:s')), array('revert_id' => $revert_id));
	}


	function get_countries_dd()
	{
		$data = $this->get_all('country');
			
		//country_code -> country_id aug 26, 2011
		return $this->get_dropdown_equivalent('iso', 'country', $data);
	}

	function get_countries_dd_phonebook()
	{
		$data = $this->get_all('country');

		//country_code -> country_id aug 26, 2011
		return $this->get_dropdown_equivalent('iso', 'country', $data,false,"All Countries");
	}
	
	function get_countries_dd_mobile_no()
	{
		$data = $this->get_all('country');
	
		//country_code -> country_id aug 26, 2011
		return $this->get_dropdown_equivalent('country_id', 'country', $data,false,"All Countries");
	}
	
	function get_operators_dd()
	{
		$data = $this->get_where('operator',array('default' =>'0'));
	
		//country_code -> country_id aug 26, 2011
		return $this->get_dropdown_equivalent('operator_id', 'operator_code', $data,false,"Operator");
	}

	function get_countries_dd_reports()
	{
		$data = $this->get_all('country');

		//country_code -> country_id aug 26, 2011
		return $this->get_dropdown_equivalent('iso', 'country', $data,NULL,'all countries');
	}

	function get_dropdown_equivalent($key, $value, $data, $remove_select = FALSE, $default = NULL, $no_data = NULL, $prefix = "Select ")
	{
		$data_new = array();
		if(sizeof($data))
		{
			if(! $remove_select)
				if($default)
				$data_new[''] = "$prefix ". $default;
			else
				$data_new[''] = "$prefix ". ucfirst(str_replace('_', ' ',$value));

			foreach($data as $d)
			{
				//remove blank values
				if($d->$value)
					$data_new[$d->$key] = $d->$value;
			}
		}
		else
		{
			if(! $no_data)
				$data_new[''] = 'No selection possible';
			else
				$data_new[''] = $no_data;
		}
		return $data_new;
	}
	

	function get_timezones()
	{
		static $regions = array(
				'Africa' => DateTimeZone::AFRICA,
				'America' => DateTimeZone::AMERICA,
				'Antarctica' => DateTimeZone::ANTARCTICA,
				'Aisa' => DateTimeZone::ASIA,
				'Atlantic' => DateTimeZone::ATLANTIC,
				'Europe' => DateTimeZone::EUROPE,
				'Indian' => DateTimeZone::INDIAN,
				'Pacific' => DateTimeZone::PACIFIC
		);

		foreach ($regions as $name => $mask) {
			$tzlist[] = DateTimeZone::listIdentifiers($mask);
		}

		print_r($tzlist);
	}

	function get_static_permissions($level = NULL)
	{
			
		$table = 'quick_links';
			
		if($level)
		{
			$filter = array('min_level' => "$level");
			$this->db->order_by('order', 'ASC');
			return $this->get_where($table, $filter);
		}
		else
		{
			$this->db->order_by('order', 'ASC');
			return $this->get_all($table);
		}
	}

	function get_default_permission($level)
	{
		//get all the permissions with min_level <= $level


		for($i = $level; $i > 0; $i--)
		{

			$permissions = $this->get_static_permissions($i);

			foreach($permissions as $p)
				$temp[] = $p->permission_id;
		}
		return implode(',', $temp);
	}

	function get_routes($noblock = false)
	{
		
		if($noblock)
			$this->db->where('route_id <>', 0);
			
		$data = $this->get_all('route', 'route_id', 'ASC');
		return $data;
	}

	function get_currencies($currency_id = NULL)
	{
		if($currency_id)
		{
			$table = 'currency';
			$filter = array('currency_id' => $currency_id);
			return $this->get_row($table, $filter);
		} else {
			return $this->get_all('currency');
		}
	}

	function get_currencies_dd()
	{
		$data = $this->get_all('currency');
			
		return $this->get_dropdown_equivalent('code', 'code', $data, TRUE);
	}



	function get_routes_dd()
	{
		$this->db->order_by('route_id', 'ASC');
		$data = $this->get_all('route');
		return $this->get_dropdown_equivalent('route_id', 'route_code', $data, TRUE);
	}



	function get_announcements()
	{
		$data = $this->get_all('announcement');
		return $data;
	}

	function reload_blacklist($data)
	{
		$table = 'blacklist';
		$this->db->empty_table($table);
		
		
		foreach($data as $d)
		{
			$d['word'] = trim($d['word']);
			$this->insert_entry($table, $d);
		}
	}

	//    function reload_pricing($account_type, $data)
	//    {
	//    	$table = 'default_'.$account_type.'_pricing';
	//
	//		$this->db->empty_table($table);
	//
	//		foreach($data as $d)
	//		{
	//    		unset($d['country']);
	//    		unset($d['operator_name']);
	//			$this->insert_entry($table, $d);
	//    	}
	//    }

	function reload_pricing($data)
	{
			
		//this will change the operator price
		//uploading should change the custom
			
		foreach($data as $d)
		{
			$this->update_entry('operator', array('price' => $d['price']), array('operator_id' => $d['operator_id']));
		}
	}

	function reload_operators($data)
	{
		$table = 'operator';
			
		$this->db->empty_table($table);
			
		foreach($data as $d)
		{

			unset($d['country']);
			$this->insert_entry($table, $d);
		}

		$this->reload_default_tables();
	}

	function get_default_pricing($table, $offset = NULL, $limit = NULL)
	{

		$sql = "SELECT t.price, o.operator_code, country, o.operator_id, o.country_id, o.operator_name
		FROM $table t
		LEFT JOIN operator o ON o.operator_id = t.operator_id
		LEFT JOIN country c ON c.country_id = o.country_id
		WHERE o.country_id > 0
		ORDER BY c.country_id ASC, o.operator_name ASC

		";

		//		$this->db->select($table.'.price, operator.operator_name, country, operator.operator_id');
		//		$this->db->from($table);
		//		$this->db->order_by('country.country_id', 'ASC');
		//		$this->db->order_by("operator.operator_name", "asc");
		//		$this->db->join('operator', "operator.operator_id = $table.operator_id", 'left');
		//		$this->db->join('country', 'country.country_id = operator.country_id', 'left');
		//		$query = $query = $this->db->get();
		//		$data =  $query->result();

		if($limit)
			return $this->get_sql($sql, $offset, $limit);
		else
			return $this->get_sql($sql);

	}

	function reload_routes($data)
	{
		$table = 'route';
			
		$this->db->empty_table($table);
			
		foreach($data as $d)
		{

			$this->insert_entry($table, $d);
		}
	}

	function reload_default_tables()
	{
		//this will just copy all the operators from the operator table
		//then put it all to the default tables
		//client, affiliate, reseller

		$this->empty_table('default_affiliate_pricing');
		$this->empty_table('default_client_pricing');
		$this->empty_table('default_reseller_pricing');

		$data = $this->get_operators();

		foreach($data as $d)
		{
			$new_data = array(
					'operator_id' => $d->operator_id,
					'price'		  => $d->price
			);
			$this->insert_entry('default_affiliate_pricing', $new_data);
			$this->insert_entry('default_client_pricing', $new_data);
			$this->insert_entry('default_reseller_pricing', $new_data);
		}
	}

	function get_accounts()
	{
		return $this->get_all('account');
	}
	
	function get_accounts_dd()
	{
		return $this->get_dropdown_equivalent('account_id','account_name',$this->get_accounts());
	}

	function get_templates($account_id)
	{
		$filter	=	array('account_id' =>$account_id);
		return $this->get_where('template', $filter);
	}

	function get_operators(){

		//LEFT JOIN route r 			ON r.route_id = o.route_id

		$sql = "
		SELECT o.operator_id, c.country, o.operator_name, o.operator_code, o.price, o.reseller_price, o.route_id, rp.cost_price
		FROM operator o
			
		LEFT JOIN country c 		ON c.iso = o.country_id
		LEFT JOIN route_price rp 	ON rp.route_id = o.route_id and rp.operator_id = o.operator_id
		ORDER BY c.country ASC, operator_name ASC
			
		";

		return $this->get_sql($sql);
	}

	function get_operators_clients()
	{
		$this->load->model('account_mo');

		$data['operators'] 	= $this->get_operators();
		$data['accounts']  	= $this->get_accounts();
		$data['pricing'] = array();

		foreach($data['operators']  as $o)
		{
			foreach($data['accounts']  as $a)
			{
				$price	= $this->account_mo->get_pricing($a->account_id, $o->operator_id);
				$data['pricing'][$o->operator_id][$a->account_id] = $price[0];
			}
		}

		return $data;
	}

	function get_prefixes()
	{
		$this->db->order_by('prefix_number','DESC');
		return $this->get_all('prefix');
	}

	function add_blacklist($data)
	{
		$this->insert_entry('blacklist', $data);
	}

	function get_blacklist($blacklist_id = NULL)
	{
		if($blacklist_id)
		{
			$table = 'blacklist';
			$filter = array('blacklist_id' => $blacklist_id);
			return $this->get_row($table, $filter);
		} else {
			return $this->get_all('blacklist');
		}
	}

	function update_blacklist($data, $blacklist_id)
	{
		$filter = array('blacklist_id' => $blacklist_id);

		$this->update_entry('blacklist', $data, $filter);
	}

// 	function broadcast_announcement($id)
// 	{

// 		$filter = array('announcement_id' => $id);
// 		$this->delete_entry('user_inbox', $filter);

// 		$message = $this->get_row('announcement', $filter);

// 		unset($message->type);
// 		unset($message->status);



// 		//for clients level 1 and 2
// 		if($data->type == '1')
// 		{
// 			$sql 		= "SELECT * from user where level < 3";
// 		}
// 		//for level 3 and 4
// 		else if($data->type == '2')
// 		{
// 			$sql 		= "SELECT * from user where level < 5 and level > 2";
// 		}
// 		else
// 		{
// 			$sql 		= "SELECT * from user";
// 		}

// 		$users 	= $this->get_sql($sql);

// 		foreach($users as $u)
// 		{
// 			$message->user_id = $u->user_id;
// 			$this->insert_entry('user_inbox', $message);
// 		}
// 	}

	function get_banner_announcement()
	{
		$sql = "SELECT message,subject from announcement where type = '4' ORDER BY date_created DESC";

		$result =  $this->get_sql($sql, 0, 1);

		if(is_array($result))
			return $result[0];
		else
			return -1;
	}

	function get_level_desc($level)
	{
		switch($level)
		{
			case 1: return "Sub user"; break;
			case 2: return "Main user"; break;
			case 5: return "Promotexter Admin"; break;
			default: return "Invalid level";
		}
	}

	function check_permissions($all_permissions, $permissions_array)
	{



		foreach($all_permissions as $p)
		{
			$p->checked = "";

			foreach($permissions_array as $pa)
			{
				if($p->permission_id == $pa){
					$p->checked = "checked";
					unset($pa);
					break;
				}
			}
		}
			
		return $all_permissions;
	}

	function get_account($account_id)
	{
		$filter = array('account_id' => $account_id);
		$table = 'account';

		$this->db->select('*, account.date_created,account.created_by');
		$this->db->join('country','account.country_id = country.iso', 'left');
		// $this->db->join('currency','account.currency = currency.code', 'left');
		$query = $this->db->get_where($table,$filter);

		$data = $query->row();


		return $data;
	}
	
	function get_account_admin($filter)
	{
		$table = 'account';
	
		$this->db->select('*, account.date_created,account.created_by');
		$this->db->join('country','account.country_id = country.iso', 'left');
		$query = $this->db->get_where($table,$filter);
	
		$data = $query->row();
	
	
		return $data;
	}
	

	function extract_prefix($mobile_no)
	{

// 		$prefix 	= $this->prefix_match( $this->get_prefixes(), 'prefix_number', $mobile_no);
		$prefix 	= $this->prefix_match($mobile_no);
		
		// echo "HERe";
		// die();
		//prefix found

		if(is_object($prefix))
		{
				
			$data['prefix_type'] 	=   'operator';
			$data['id']				= $prefix->operator_id;
			$data['country_id']		= $prefix->country_id;
			$data['iso']			= $prefix->iso;
			$data['prefix_number']	= $prefix->prefix_number;
			$data['operator']		= $prefix->operator_code;
			$data['prefix_id']		= $prefix->prefix_id;
			$data['sender_id_whitelisted']		= $prefix->sender_id_whitelisted;
			$data['cost_price']			= $prefix->cost_price;
			
			return $data;

			// echo "found prefix";
		}
		else
		{
			// echo "MOBILE NO $mobile_no";
		}

		return null;
	}
	
	function get_custom_route($mobile,$account_id)
	{
		//get prefix
		$this->get_prefix($mobile,$account_id);
		
		//then check for custom routing
	}

	function get_prefix($mobile_no, $account_id, $active = false)
	{

		$this->load->model('account_mo');
		$this->load->model('accounting_mo', 'accounting');

		$result = $this->extract_prefix($mobile_no);

		// die('here');

		// echo "HERE";
		if($result)
		{
			$data = $this->account_mo->get_pricing($account_id, $result['id']);

			$data = $data[0];

			// log_message('error',"GET PREFIX: " . json_encode($data));
			
			if($account_id)
			{
				if($data->xrate)
					$data->price = $data->price / $data->xrate;
				else
					$data->price = $this->accounting->convert_amount($account_id,$data->price, '-');
			}
			
			//add prefix id
			
			$data->prefix_id 		= $result['prefix_id'];
			$data->prefix_number 	= $result['prefix_number'];
			$data->sender_id_whitelisted 	= $result['sender_id_whitelisted'];
			$data->cost_price 		= $result['cost_price'];
			
			return $data;
		}
		else
		{
			log_message('error',"NO PREFIX MATCH " . $mobile_no);
			return -1;
		}
	}

	function get_operator_2($filter)
	{

		$this->db->select('country_id, operator_id, route_id, price');
		$this->db->where($filter);
		$query = $this->db->get('operator');

		return $query->result();
	}
	
	function prefix_match_new($string)
	{
		$sql = "
				SELECT prefix from phonebook where phonebook_id = '$string'
		";
		
		$data 	= $this->get_sql($sql);
		
		$pb_row = $data[0];
		
		if(strlen($pb_row->prefix)) //if there is a prefix, use it
		{
			
		}
		else
		{
			//use the old way of determining prefix
		}
	}

	function get_prefix_by_id($prefix_id)
	{
		$sql = "
				SELECT c.country_id,c.iso,o.route_id,o.operator_id,prefix_number,prefix_id
				from prefix p
				LEFT JOIN operator o ON o.operator_id = p.operator_id
				LEFT JOIN country c ON c.iso = o.country_id
				LEFT JOIN route r ON r.route_id = o.route_id
				WHERE prefix_number = '$prefix_id'
		";
		
		$result = $this->get_sql($sql);
		
		return $result[0];
	}
	
	function get_prefix_match($string)
	{
		$sql = "
			SELECT c.country_id,c.iso,o.route_id,
				o.operator_id,prefix_number,prefix_id,
				operator_name,operator_code,
				route_name,sender_id_whitelisted,rp.cost_price
			from prefix p
			LEFT JOIN operator o ON o.operator_id = p.operator_id
			LEFT JOIN country c ON c.iso = o.country_id
			LEFT JOIN route r ON r.route_id = o.route_id
			LEFT JOIN route_price rp 	ON rp.route_id = o.route_id and rp.operator_id = o.operator_id
			WHERE prefix_number = '$string'
		";

		// WHERE prefix_number LIKE '$string%'
		
		$result = $this->get_sql($sql);
		

		return $result[0];
	}
	
	function prefix_match($string)
	{
		$mobile_no = $string;
		if(PREFIX_FIX_IS_ACTIVATED == true)
		{
			$sql = "
					SELECT prefix_id from phonebook where mobile_no = '$string'
			";
			
			$data 	= $this->get_sql($sql);
			
			$pb_row = $data[0];
			$prefix_id	= $pb_row->prefix_id; 
			
			if($prefix_id) //if there is a prefix, use it
			{
				return $this->get_prefix_by_id($prefix_id);
			}
			else
			{
				//use the old way if no prefix was found
				
				while(strlen($string) > 0)
				{
					if($prefix = $this->get_prefix_match($string))
					{

						$this->update_entry('phonebook',
							array('prefix_id' 		=> $prefix->prefix_number,
								   'operator_id'    => $prefix->operator_id,
								   'country_id'     => $prefix->iso
										),
							array('mobile_no' 	=> $mobile_no)
						);
						
						return $prefix;
					}
					else
					{
						$string = substr($string,0,-1);
					}
				}
				
				return -1;
			}
		}
		else
		{
			while(strlen($string) > 0)
				{
					if($prefix = $this->get_prefix_match($string))
					{
						// echo $string;

						
						$this->update_entry('phonebook',
								array('prefix_id' 		=> $prefix->prefix_number,
								   'operator_id'    => $prefix->operator_id,
								   'country_id'     => $prefix->country_id
										),
								array('mobile_no' => $mobile_no));
						
						// log_message("error","prefix_match $mobile_no");

						// log_message("error",json_encode($prefix));
						
						return $prefix;
					}
					else
					{
						$string = substr($string,0,-1);
					}
				}
				
				return -1;
		}
	}

	//this is called by the admin controller, should return all users under him, probably in the range of 3 <= level <= 5
	//    function get_admin_users()
	//    {
	//		$sql = "SELECT *
	//						FROM user
	//						WHERE level > 2
	//				";
	//		return $this->get_sql($sql);
	//    }


	function get_add_ons($account_id = NULL, $status = NULL)
	{
			
		if($account_id)
		{
			$status_filter = ($status)? "AND aao.status = '$status'" : "";


			$sql = "
			SELECT *
			FROM account_add_on aao
			LEFT JOIN add_on ao on aao.add_on_id = ao.add_on_id
			WHERE aao.account_id = $account_id
			$status_filter
			";
			$query = $this->get_sql($sql);

			$data = array();
			foreach($query as $q)
				$data[] = $q->code;
				

			return $data;
		}
		else
			return $this->get_all('add_on');
	}

	function check_add_ons($account_id)
	{
		$sql = "SELECT *,
		(SELECT status from account_add_on aao
		WHERE aao.account_id = $account_id and aao.add_on_id = ao.add_on_id) status
		FROM add_on ao
		";
		$data = $this->get_sql($sql);
			
		return $data;
	}

	function get_add_on($account_id, $id = null, $code = null)
	{
		
			
		$sql = "SELECT *,
		(SELECT status from account_add_on aao
		WHERE account_id = $account_id and add_on_id = $id) status
		FROM add_on ao
		WHERE ao.add_on_id = $id

		";
		$data = $this->get_sql($sql);
		$data = $data[0];
			
			
		/*** added for customized account pricing **********************************************/
		$type = $data->code;
			
		$sql = "
		SELECT cost
		FROM account_static_pricing
		WHERE code = '$type' and account_id = $account_id
		";

		$data2 = $this->get_sql($sql);
		if($data2)
		{
			$data->setup_cost = $data2[0]->cost;
		}
		/*** added for customized account pricing **********************************************/

		return $data;
			
	}

	function multi_request($data, $options = array(),$timeout = 10) {

		// array of curl handles
		$curly = array();
		// data to be returned
		$result = array();

		// multi handle
		$mh = curl_multi_init();

		// loop through $data and create curl handles
		// then add them to the multi-handle
		foreach ($data as $id => $d) {

			$curly[$id] = curl_init();

			$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;

			curl_setopt($curly[$id], CURLOPT_URL,            $url);
			curl_setopt($curly[$id], CURLOPT_HEADER,         0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, $timeout);


			// post?
			if (is_array($d)) {
				if (!empty($d['post'])) {
					curl_setopt($curly[$id], CURLOPT_POST,       1);
					curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
				}
			}

			// extra options?
			if (!empty($options)) {
				curl_setopt_array($curly[$id], $options);
			}

			curl_multi_add_handle($mh, $curly[$id]);
		}

		// execute the handles
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);

		// get content and remove handles
		foreach($curly as $id => $c) {
			$result[$id] = curl_multi_getcontent($c);
			curl_multi_remove_handle($mh, $c);
		}

		// all done
		curl_multi_close($mh);

		return $result;

	}

	

	function get_admins()
	{
		$sql = "SELECT * from admin_user";
		return $this->get_sql($sql);
	}

	function get_freebies($type)
	{
		$sql = "SELECT * from static_freebies where type = '$type'";

		$data = $this->get_sql($sql);

		$free =  array();
		foreach($data as $d)
		{
			$free[$d->code] = $d->value;
		}

		return $free;
	}

	function datatable_paging($aColumns, $sIndexColumn, $sTable, $filter, $joins = NULL, $custom = NULL,$group = null, $having = null)
	{
		if($joins)
			foreach($joins as $j)
			$this->db->join($j[0],$j[1],$j[2]);
			
		$t = array();
		if ( $_GET['sSearch'] != "" )
		{
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				$t[$aColumns[$i]] =  $_GET['sSearch'];
			}
		}
			
		/* Individual column filtering */
			
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
			{
				$t[$aColumns[$i]] =  $_GET['sSearch'];
			}
		}
		$this->db->or_like($t);

		if($filter)
			$this->db->where($filter);
			
		if($group)
			$this->db->group_by($group);

		// if($having)
		// 	$this->db->having($having);
	

		if($custom)
			$this->db->where($custom);

		$query2 = $this->db->count_all_results($sTable);
			

			
		if($joins)
			foreach($joins as $j)
			$this->db->join($j[0],$j[1],$j[2]);
			
		$this->db->or_like($t);
		


		/*
		 * Paging
		*/
		if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
		{
			$this->db->limit($_GET['iDisplayLength'],$_GET['iDisplayStart']);
		}
			
		/*
		 * Ordering
		*/
		if ( isset( $_GET['iSortCol_0'] ) )
		{
			for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
			{
				if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
				{
					$this->db->order_by($aColumns[ intval( $_GET['iSortCol_'.$i] ) ],mysql_real_escape_string( $_GET['sSortDir_'.$i] ));
				}
			}
		}
			
		/*
		 * SQL queries
		* Get data to display
		*/
		
		if($group)
			$this->db->group_by($group);
			
		if($having)
			$this->db->having($having);

		$this->db->select(implode(',',$aColumns));
			
		if($filter)
			$this->db->where($filter);
		
		if($custom)
			$this->db->where($custom);
		
		$query = $this->db->get($sTable);
		$rResult = $query->result();


		$aaData = array();
		foreach($rResult as $rr)
		{
			$row = array();
			$i = 0;
			foreach($rr as $k => $v)
			{
				$row[$i++] = $v;
			}

			$row['DT_RowId'] = $rr->$sIndexColumn;

			$aaData[] = $row;
		}
			
		/*
		 * Output
		*/
		$output = array(
				"sEcho" => intval($_GET['sEcho']),
				"iTotalRecords" => $query2,
				"iTotalDisplayRecords" => $query2,
				"aaData" => $aaData
		);
			
		return $output;
	}
	
	function is_connected($url,$timeout = 10)
	{
		
		$handle = curl_init($url);
		
		//curl_setopt($handle, CURLOPT_TIMEOUT, 2);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
	
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
	
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		
		curl_close($handle);
	
		if($httpCode == 404 || $httpCode == 0) {
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function simple_curl($url)
	{
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
		$output = curl_exec($ch);
	
		log_message('error', 'CURL OUTPUT '. $output);
		
		curl_close($ch);
	
		return $output;
	}
	
	function curl_with_error($url)
	{
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
		$output = curl_exec($ch);
		$error = curl_error($ch);
		$info = curl_getinfo($ch);
	
		log_message('error', 'CURL OUTPUT '. $output);
		
		curl_close($ch);
	
		return array("out" => $output, "err" => $error, "info" => $info);
	}
	
	function get_other_pricing()
	{
		$sql = "
				SELECT cost, sc.code, sc.description, sc.cost_details
					FROM static_cost sc
		";
	
	
		$data = $this->get_sql($sql);
	
	
		for($i=0; $i < sizeof($data); $i++)
		{
			$data[$i]->cost = $data[$i]->cost;
		}
	
		return $data;
	}
	
	function update_other_pricing($data)
	{
		foreach($data as $k => $v)
		{
			$this->update_entry('static_cost',array('cost' => $v),array('code' => $k));
		}
	}
	
	
	
	// function send_alert_sms($message)
	// {
	// 	//if(ENVIRONMENT != 'production')
	// 		//return;
	
	// 	$message = urlencode($message);
	
	// 	if(ENVIRONMENT == 'development')
	// 		$admins = array('09052667996');
	// 	else
	// 		$admins = array('09052667996','09178802646');
	
	// 	foreach($admins as $a)
	// 	{
	// 		$result = $this->simple_curl("http://222.127.178.56:8801/?PhoneNumber=$a&text=$message");
	// 		// $result = $this->simple_curl("http://10.0.0.10:8801/?PhoneNumber=$a&text=$message");
	// 	}
	// }

	function send_alert_sms($message, $admins = array('09052667996', '09178802646','09272011567')) 
	{
	
		if ($this->cache->get('ALERT_SENT')) //there is already an entry
 		{
			return; //same minute so just move on		
		}
		else
		{
			log_message('error', 'SENDING ALERT');
		}

		$message = urlencode($message);		

		foreach ($admins as $a) {
			$result = $this->simple_curl("http://222.127.178.56:8801/?PhoneNumber=$a&text=$message");
		}


		$this->cache->set('ALERT_SENT',1,60 * 5); //send alert every 5 minutes
	}

	function get_general_setting($code)
	{
		$table = 'general_settings';
		$filter = array('code' => $code);
		$data = $this->get_row($table, $filter);

		return $data->val;
	}

	function set_general_setting($code, $val)
	{
		$table = 'general_settings';
		$data = array('val' => $value);
		$filter = array('code' => $code);

		$this->update_entry($table, $data, $filter);

		
	}

	function insert_unique($table, $filter, $additional_data = null, $counter = null)
	{

		// log_message('error',"insert unique :" . json_encode($filter) . "COUNTER : $counter");

		if($this->row_exist($table,$filter))
		{
		
			//if counter is set, increment it
			if($counter)
			{	
				$this->db->set($counter,"$counter + 1",false);
				$this->db->where($filter);
				$this->db->update($table);
			}
			
		}
		else
		{
		
			//row does not exist, insert new row

			if($additional_data)
			{
				echo "here3";
				$data = array_merge($filter,$additional_data);
			}
			else
			{
				$data = $filter;
			}

			$this->db->insert($table,$data);
		}
	}
	
	function getDatesBetween2Dates($startTime, $endTime) {
	    $day = 86400;
	    $format = 'Y-m-d';
	    $startTime = strtotime($startTime);
	    $endTime = strtotime($endTime);
	    $numDays = round(($endTime - $startTime) / $day) + 1;
	    $days = array();
	        
	    for ($i = 0; $i < $numDays; $i++) {
	        $days[] = date($format, ($startTime + ($i * $day)));
	    }
	        
	    return $days;
	}	

	function sender_id_whitelist_check($operator_id = null,$sender_id)
	{
		$sender_id = preg_replace('/[^a-zA-Z0-9\ \_]+/','',$sender_id);

		$sender_id = preg_replace('/\_/',' ',$sender_id);
		
		// log_message('error',"sender_id_whitelist_check $sender_id");
		
		if($operator_id) //check if the sender id is whitelisted to the current operator
		{

			if($this->row_exist('sender_id_whitelisted',array('operator_id' => $operator_id, 'sender_id' => $sender_id)))
			{
				return true;
			}
		}
		else
		{

			//just check if the sender id is in the whitelisted senderids


			if($this->row_exist('sender_id_whitelisted',array('lower(sender_id)' => strtolower($sender_id))))
			{


				return true;
			}
		}

		return false;
	}


	function convert_account_localtime($account,$datetime)
	{
		//get account
		$account = $this->get_account($account);

		$offset 					= timezones($account->timezones);
		$offset_str 				= timezones_str($account->timezones);

		echo $offset;
		echo "<BR>";
		echo $offset_str;
	}

	function opt_out_sms($account_id,$mobile_no)
	{
		$data = array(
			'account_id' 	=> $account_id, 
			'mobile_no' 	=> $mobile_no
		);

		$this->db->update('phonebook', array('status' => 'OPTD_OUT'), $data);

		$this->db->insert('opt_out', $data);
	}

	function opt_in_sms($account_id,$mobile_no)
	{
		$data = array(
			'account_id' 	=> $account_id,
			'mobile_no' 	=> $mobile_no
		);

		$this->db->update('phonebook', array('status' => 'ACTIVE'), $data);

		$this->db->insert('opt_in', $data);
	}
	
}