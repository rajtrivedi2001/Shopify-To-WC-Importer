<?php 
class shopify_json{
	public $total_products;
	public $total_product_pages;
	public $total_collections;
	public $total_collections_pages;
	
	
	public function __construct($runRequest = false) {
		define('log_type','json_generator');
		$this->set_variables();
		if($runRequest){
			$this->run_generator();
		}		
	}

	
	private function set_variables(){
		$this->total_products = '';
		$this->total_products_pages = '';
		$this->total_collections = '';
		$this->total_collections_pages = '';
	}


	private function run_generator(){
		$this->get_total_products();
		$this->get_total_collections();
		/*****************************/		
		$this->get_product_total_pages();
		$this->get_collections_total_pages();
		/*****************************/
		$this->get_product_json();
		$this->get_collections_json();
	}	
	
	public  function get_total_products(){
		$request = request(product_count_url);
		save_file(product_count_file,json_encode($request));
		$this->total_products = $request['count'];
		text($request['count'].' Total Products Found');
		return $request['count'];
	}
	
	public function get_product_total_pages(){
		$loop_count = round($this->total_products/product_fetch_limit) + 1;
		$this->total_product_pages = $loop_count;
		text($loop_count.' Total Pages Found');
		return $loop_count;
	}
	
	public function get_product_json(){
		$page_count = $this->total_product_pages;
		text('<hr/>',false);
		$i = 1;
		while($i <= $page_count){
			text('Gettings Product JSON For Page '.$i);
			$url = product_json_url.'?limit='.product_fetch_limit.'&page='.$i;
			$request = request($url); 
			$filename = str_replace('{$count}',$i,product_json_file);
			
			if(is_array($request) && !empty($request['products'])){
				text('Saving Product JSON For Page '.$i);
				text($filename);
				save_file($filename,json_encode($request));
			} else {
				text('Error Getting JSON FOR  '.$url);
			}
			$i++;
			text('<hr/>',false); 
		} 
	}
	
	
	
	


	public  function get_total_collections(){
		$request = request(collections_count_url);
		save_file(collections_count_file,json_encode($request));
		$this->total_collections = $request['count'];
		text($request['count'].' Total Collections Found');
		return $request['count'];
	}
	
	public function get_collections_total_pages(){ 
		if($this->total_collections < collections_fetch_limit){
			$this->total_collections_pages = 1;
			text('1 Total Collections Found');
			return 1;
		} else {
			$loop_count = round($this->total_collections/collections_fetch_limit) + 1;
			$this->total_collections_pages = $loop_count;
			text($loop_count.' Total Collections Found');
			return $loop_count;			
		}
		
	}
	
	public function get_collections_json(){
		$page_count = $this->total_collections_pages;
		text('<hr/>',false);
		$i = 1;
		while($i <= $page_count){
			text('Gettings Collections JSON For Page '.$i);
			$url = collections_json_url.'?limit='.collections_fetch_limit.'&page='.$i;
			$request = request($url);
			$filename = str_replace('{$count}',$i,collections_json_file);
				
			if(is_array($request) && !empty($request['custom_collections'])){
				text('Saving Collections JSON For Page '.$i);
				text($filename);
				save_file($filename,json_encode($request));
			} else {
				text('Error Getting JSON FOR  '.$url);
			}
			$i++;
			text('<hr/>',false);
		}
	}	 

  
		
	public function get_collections_product_json($product_id){
		$page_count = $this->total_collections_pages;
		$url = collections_json_url.'?limit='.collections_fetch_limit.'&product_id='.$product_id.'&page='.$i;
		$request = request($url);
		return $request;
	}	
} 

?>
