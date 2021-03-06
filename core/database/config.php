<?php 
/*
Config Database Controller

Author: Mathias Beke
Url: http://denbeke.be
Date: July 2014
*/

namespace Database;


/**
Config Database Controller
*/
class Config {

	const CONFIG = 'Config';


	/**
	Read the stored config data
	
	@return `['config_key' => 'config_value']`
	*/
	static public function read() {

		$query = BUILDER::table(self::CONFIG)->select('*');
		
		$result = $query->get();
		$output = [];

		foreach ($result as $item) {
			
			$output[$item->config_key] = $item->config_value;
			
		}

		return $output;

	}
	
	
	/**
	Save the given config data item in the database
	
	If the given config doesn't exist,
	a new entry will be created in the database.
	
	The function will store an array of config data items
		[
			'config_key' => 'config_value'
		]
		
	This makes it easy to save a batch of configuration options
	
	@param config data
	*/
	static public function save($config_data) {
		
		foreach ($config_data as $config_key => $config_value) {
			
			if($config_key == '' or $config_key == NULL) {
				throw new \exception('No config key given');	
			}
			else {
				self::saveSingle($config_key, $config_value);	
			}
			
		}
		
	}
	
	
	/**
	Check if the given config item exists
	
	@param config key
	@return exists
	*/
	static public function exists($config_key) {
		
		$query = BUILDER::table(self::CONFIG)->where('config_key', '=', $config_key);
		$count = $query->count();
		
		if($count == 1) {
			return true;
		}
		else {
			return false;
		}
		
	}
	

	/**
	Save a single config item to the databases
	
	If the given config doesn't exist,
	a new entry will be created in the database.
	
	@param config key
	@param config value
	*/
	static private function saveSingle($config_key, $config_value) {
		
		if(!self::exists($config_key)) {
			
			//If not exists, we make new item
			$data = [
				'config_key' => $config_key,
				'config_value' => $config_value
			];
			
			BUILDER::table(self::CONFIG)->insert($data);
	
		}
		else {
			
			//Else, we update the item
			$data = [
				'config_value' => $config_value
			];
			
			BUILDER::table(self::CONFIG)->where('config_key', '=', $config_key)->update($data);
			
		}
		
		
		
	}


}



?>