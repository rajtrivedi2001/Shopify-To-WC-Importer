<?php 
require ( ABSPATH . 'wp-admin/includes/image.php' );
class product_importer{ 
	private $json;
	private $total_product_c;
	private $total_products;
	private $total_collection; 
	public $current_product;
	public $current_product_id;
	public $created_attribute;
	public $current_product_type;
	public $collectionJSON;
	public $current_product_attribute;
	/**
	 * INITS & Runs The Class
	 */
	public function __construct() {
		global $json_gen;
		define('log_type','product_importer');
		
		$this->json = $json_gen;
		$this->created_attribute = array();
		$this->current_product_attribute = array();
		$this->loop_count = 1;
		$this->get_jsons_file_count(); 
		
		
		$this->createAttribute(product_vendor_term);
		$this->createAttribute(product_type_term);
		$this->createAttribute('Size');
		$this->createAttribute('Colour');
		$this->createAttribute('Weight');
		$this->createAttribute('Liter');
		$this->createAttribute('CC');
		$this->createAttribute('Other');
		
		$this->get_collection_json(); 
		$this->get_product_json();
	} 	        
	 
	
	/**
	 * Retrives Total Number JSON File's Aviable
	 */
	public function get_jsons_file_count(){
		$this->total_product_c = $this->json->get_total_products();
		$this->json->get_total_collections();
		$this->total_products = $this->json->get_product_total_pages();
		$this->total_collection = $this->json->get_collections_total_pages();
	}
	
	/**
	 * Get Each Collection JSON File  And Decode IT AND Runs IT
	 */
	public function get_collection_json(){
		$collectionJSON = json_decode(file_get_contents(dataFOLDER.'collections.json'),true); 
		$this->collectionJSON = $collectionJSON;
	}
	
	
	/**
	 * Get Each Product JSON File And Decode IT AND Runs IT
	 */	
	public function get_product_json(){
		$count = 1;
		while($count<=$this->total_products){
			$filename = str_replace('{$count}',$count,product_json_file);
			$product_json = json_decode(file_get_contents(dataFOLDER.$filename),true);
			
			if(isset($product_json[products_base_key]) && !empty($product_json[products_base_key])){
				#$this->get_all_options_list($product_json[products_base_key]);
				$this->read_product_json($product_json[products_base_key]);
			}
			$count++; 
		}	
	}
	
	
	/**
	 * Loops Every Product
	 * @param unknown $json
	 */
	private function read_product_json($json){
		text('<hr/>',false);
		foreach($json as $product){
			text('Creating '.$this->loop_count.' of '.$this->total_product_c );
			$this->current_product = $product;

			if(count($product['variants']) > 1){
				$this->current_product_type = 'variable'; 
			} else {
				$this->current_product_type = 'simple';
			}		
		  $this->createProduct();
		  exit();
			$this->loop_count++;
		}		
	}
	
	/**
	 * Creates Bease product
	 */
	public function createProduct(){
		$product = $this->current_product;
		text('Creating '.$product['title']);
		
		$post = array(
				'post_author' => product_author,
				'post_content' => $product['body_html'],
				'post_status' => product_status,
				'post_title' => $product['title'],
				'tags_input' => $product['tags'],
				'post_type' => product_post_type,
		);
	
		$this->current_product_id = wp_insert_post($post,$wp_error);
		$this->add_category();
		$this->add_tags();
		$this->add_images();
		$this->update_post_metas();
		
		text('Finished Creating Product '. $product['title']);
		text('<hr/>',false);
	}	
	


	/**
	 * Adds Category To Post
	 */
	public function add_category(){
		text('Creating / Mapping Category To Product'); 
		$termid = $this->create_tax_term($this->current_product['product_type'],product_category_term);
		wp_set_post_terms($this->current_product_id, $termid,product_category_term);
	} 



	/**
	 * Adds Tag To Post
	 */
	public function add_tags(){
		text('Creating / Mapping Tags To Product ');
		$tags = explode(', ',$this->current_product['tags']);
		foreach($tags as $tag){
			$this->create_tax_term($tag,product_tag_term);
			wp_set_post_terms($this->current_product_id, $tag,product_tag_term,true);
		} 
	}
	
	/**
	 * Adds Brands To Product
	 */
	public function add_vendor(){
		text('Create / Mapping Brands To Product');
		$termid = $this->create_tax_term($this->current_product['vendor'],$this->created_attribute[product_vendor_term]);
		wp_set_post_terms($this->current_product_id, $termid,$this->created_attribute[product_vendor_term]);
	}
	
	/**
	 * Creates Term For Taxonomy
	 * @param string $term
	 * @param string $term_type
	 * @return INT [0-99999]
	 */
	private function create_tax_term($term,$term_type){
		$termid = wp_insert_term($term,$term_type);
		if(isset($termid->errors)){
			return $termid->error_data['term_exists'];
		}
		return $termid['term_id'];
		
	}
	
	
	
	/**
	 * Adds Product Images
	 */
	public function add_images() {
		text('Downloading Product Images');
		$gallery = '';
		if(count($this->current_product['images']) > 1){
			$gallery = $this->download_and_save_image('images'); 
			set_post_thumbnail(  $this->current_product_id, $gallery[0] );
			update_post_meta(  $this->current_product_id, '_product_image_gallery', implode( ',', $gallery ) );
			
		} else {
			$gallery = $this->download_and_save_image('image');
			set_post_thumbnail(  $this->current_product_id, $gallery[0] );
		}
		text('Mapping Product Images');
		
		
	}	
	
	/**
	 * Downloads And Save Images In WP
	 * @param unknown $image_array
	 * @return multitype:Ambigous <number, WP_Error>
	 */
	private function download_and_save_image($name){
		$gallery = array();
		if($name == 'image'){
			$gallery[] = $this->download_image($this->current_product[$name]['src'] );
		} else if($name == 'images'){
			foreach($this->current_product[$name]  as $image){
				$gallery[] = $this->download_image($image['src'] );
			}
		}	
		return $gallery;
	}
	

	/**
	 * Downloads Images From Remote
	 * @param unknown $src
	 * @return Ambigous <number, WP_Error>
	 */
	private function download_image($src){
		$file_name = explode("?",basename($src));
		$file_name = $file_name[0];
		$get = wp_remote_head($src);
		$type = wp_remote_retrieve_header( $get, 'content-type' );
		$mirror = wp_upload_bits($file_name, '', file_get_contents($src));
		$attachment = array( 'post_title'=>$file_name, 'post_mime_type' => $type );
		$attach_id = wp_insert_attachment( $attachment, $mirror['file'], $this->current_product_id );
		$attach_data = wp_generate_attachment_metadata( $attach_id,  $mirror['file']  );
		wp_update_attachment_metadata( $attach_id, $attach_data );		
		return $attach_id;
	}
	
	
	
	
	/**
	 * 
	 * @param unknown $product_id
	 * @param unknown $data
	 * @param string $status
	 * @param string $backorders
	 * @param string $stock
	 */
	private function set_post_meta($product_id,$data,$custom = '',$status = 'visible',$backorders ='no',$stock='no'){
			update_post_meta($product_id, '_visibility', $status);
			update_post_meta($product_id, '_sku', $data['sku'] );
			update_post_meta($product_id, '_regular_price', $data['price']);
			update_post_meta($product_id, '_sale_price', $data['compare_at_price'] );
			update_post_meta($product_id, '_price', $data['price']);
			update_post_meta($product_id, '_backorders', $backorders);
			update_post_meta($product_id, '_manage_stock', $stock);
			
			if(!empty($custom) && is_array($custom)){
				foreach($custom as $key => $val){
					update_post_meta($product_id, $key, $val);
				}
			}
			
	}

	/**
	 * 
	 * @param unknown $term
	 * @param unknown $value
	 * @param number $position
	 * @param number $visible
	 * @param number $variation
	 * @param number $tax
	 */  
	private function set_product_attribute($term,$value,$position=0,$visible=1,$variation=1,$tax=1){ 
	
		$this->current_product_attribute[ sanitize_title($term )] = array(
				'name'         => wc_clean($term),
				'value'        => $value,
				'position'     => $position,
				'is_visible'   => $visible,
				'is_variation' => $variation,
				'is_taxonomy'  => $tax
		);
	}
	
	
	/**
	 * 
	 * @param unknown $productID
	 * @return multitype:Ambigous <Ambigous <mixed, boolean, NULL, number, multitype:, stdClass>>
	 */
	public function checkCollection($productID) {
		$createType = array();
		foreach($this->collectionJSON as $collectionsKey => $collectionsValue){
			if(in_array($productID,$collectionsValue['collection_collects'])){
				if(! in_array($collectionsValue['collection_name'],$createType)){
					$createType[] = $collectionsValue['collection_name'];
				}
			}
		}
		return $createType;
	}
		
	public function update_post_metas() {
		text('Started Updating Product Meta ');
		 
		text('current product type : '.$this->current_product_type);
		
		wp_set_object_terms($this->current_product_id, $this->current_product_type, 'product_type' );
		
		if($this->current_product_type == 'simple'){
			$this->set_post_meta($this->current_product_id,$this->current_product['variants'][0]);
			$this->add_vendor();
			$this->set_product_attribute($this->created_attribute[product_vendor_term],$this->current_product['vendor'],0,0,1,1);
			
			if(isset($this->current_product['variants'][0]['inventory_quantity']) && $this->current_product['variants'][0]['inventory_quantity'] != null){
				wc_update_product_stock($this->current_product_id, intval( $this->current_product['variants'][0]['inventory_quantity'] ) );
			} else {
				wc_update_product_stock($this->current_product_id, intval(0) );
			}
			text('Adding Product Type ');
			$collections = $this->checkCollection($this->current_product['id']);
			if(!empty($collections)){
				foreach($collections as $cname){
					$term_id =$this->create_tax_term($cname,$this->created_attribute[product_type_term]);
				    wp_set_post_terms($this->current_product_id, $term_id,$this->created_attribute[product_type_term]);
				}
				$this->set_product_attribute($this->created_attribute[product_type_term],implode(",",$collections),0,0,1,1);
			}
		} else if($this->current_product_type == 'variable'){
			$others = array();
			$collections_set = array();  
			$ab_key = $this->get_varients_attribute_key();
			$custom_meta = array(); 
			
			foreach($this->current_product['variants'] as $varK => $varV){ 
				$variation_post_title = sprintf( __( 'Variation #%s of %s', 'woocommerce' ), $varV['id'], esc_html($this->current_product['title']) );
				$collections = $this->checkCollection($varV['id']);
				 
				$new_variation = array(
						'post_title'   => $variation_post_title,
						'post_content' => '',
						'post_status'  => product_status,
						'post_author'  => product_author,
						'post_parent'  => $this->current_product_id,
						'post_type'    => product_varient_post_type
				);
				 
				$variation_id = wp_insert_post( $new_variation );
				do_action( 'woocommerce_create_product_variation', $variation_id );	

				if(!empty($collections)){
					foreach($collections as $cols){
						$collections_set[] = $collections;
					}
					$custom_meta[$ab_key['type']] = sanitize_title( stripslashes( $collections[0]));
				}
								 
				if(isset($varV['inventory_quantity']) && $varV['inventory_quantity'] != null){
					wc_update_product_stock($variation_id, intval($varV['inventory_quantity'] ) );
				} else {
					wc_update_product_stock($variation_id, intval(0) );
				}
				
				if(!empty($varV['option1']) && $varV['option1'] !== null){ 
					$others[$this->checkVariationValue($varV['option1'])][] = $varV['option1']; 
				}
				if(!empty($varV['option2']) && $varV['option2'] !== null){ $others[$this->checkVariationValue($varV['option2'])][] = $varV['option2']; }
				if(!empty($varV['option3']) && $varV['option3'] !== null){ $others[$this->checkVariationValue($varV['option3'])][] = $varV['option3']; }
				$this->set_post_meta($variation_id,$varV,$custom_meta); 
			}
		}
 
		$this->add_vendor();
		$this->set_product_attribute($this->created_attribute[product_vendor_term],$this->current_product['vendor'],0,0,1,1);
		$collections = $this->checkCollection($this->current_product['id']);
		
		if(!empty($collections)){
			foreach($collections as $cname){
				$term_id =$this->create_tax_term($cname,$this->created_attribute[product_type_term]);
				wp_set_post_terms($this->current_product_id, $term_id,$this->created_attribute[product_type_term]);
			}
			$this->set_product_attribute($this->created_attribute[product_type_term],implode(",",$collections),0,0,1,1);
		}		  
		if(!empty($others)){
			foreach($others as $othk => $othv){
				if(is_array($othv)){
					foreach($othv as $v){
						$this->create_tax_term($v,$this->created_attribute[$othk]);
					} 
				}
				wp_set_object_terms($this->current_product_id, $othv, $this->created_attribute[$othk]);
				$this->set_product_attribute($this->created_attribute[$othk],implode(',',$othv));
			}
		}
		
		$custom_meta['_product_attributes'] =  $this->current_product_attribute;
		$this->set_post_meta($this->current_product_id,$varV,$custom_meta);
		text('Finished Updating Product Meta ');
	}
  	
	private function checkVariationValue($value){
		$value = strtolower($value);
		if (in_array($value, array('s','m','l'))){ return 'Size'; }	
		if (in_array($value, array('blue','pink','yellow',' yellow','orange','yellow/orange'))){ return 'Colour'; }
		if (strpos($value,'gm') !== false) { return 'Weight'; }
		if (strpos($value,'cc') !== false) { return 'CC'; }
		if (strpos($value,'ml') !== false) { return 'Liter'; }
		return 'Other';
	}
	
	
	private function get_varients_attribute_key(){
		$attribute_key = array();
		$attribute_key['brand'] = 'attribute_' . sanitize_title( $this->created_attribute[product_vendor_term] );
		$attribute_key['type'] = 'attribute_' . sanitize_title( $this->created_attribute[product_type_term] );
		$attribute_key['size'] = 'attribute_' . sanitize_title( $this->created_attribute['Size'] );
		$attribute_key['color'] = 'attribute_' . sanitize_title( $this->created_attribute['Colour'] );
		$attribute_key['weight'] = 'attribute_' . sanitize_title( $this->created_attribute['Weight'] );
		$attribute_key['liter'] = 'attribute_' . sanitize_title( $this->created_attribute['Liter'] );
		$attribute_key['cc'] = 'attribute_' . sanitize_title( $this->created_attribute['CC'] );
		$attribute_key['other'] = 'attribute_' . sanitize_title( $this->created_attribute['Other'] );
		return $attribute_key;
	}
	
	/**
	 * Creates Custom Attribute for woocommerce.
	 * @param unknown $lable
	 * @param string $name
	 * @param string $type
	 * @param string $orderby
	 * @return boolean
	 */
	public function createAttribute($lable,$name = '',$type = 'select',$orderby = 'menu_order') {
		global $wpdb,$permalinks;
		 
		if ($this->attribute_exist($lable)) {
			$this->created_attribute[$lable] = wc_attribute_taxonomy_name( $lable );
			return true;
		}  else {
			$attribute_label   = stripslashes($lable);
			$attribute_name    = '';
			$attribute_type    = 'select';
			$attribute_orderby = 'menu_order';
	
			if ( ! $attribute_label ) { $attribute_label = ucfirst( $attribute_name ); }
			if ( ! $attribute_name ) {  $attribute_name = wc_sanitize_taxonomy_name( stripslashes( $attribute_label ) ); }
	
			$attribute = array('attribute_label' => $attribute_label,
							   'attribute_name' => $attribute_name,
							   'attribute_type' => $attribute_type,
							   'attribute_orderby' => $attribute_orderby);

			$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
			$transient_name = 'wc_attribute_taxonomies';
			$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );
			set_transient( $transient_name, $attribute_taxonomies );
			$this->created_attribute[$lable] = wc_attribute_taxonomy_name( $attribute_name );
			do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );
			$action_completed = true;
			$name  = wc_attribute_taxonomy_name($lable);
	
			register_taxonomy($name,'product', array(
				'hierarchical'          => true,
				'update_count_callback' => '_update_post_term_count',
				'labels'                => array(
					'name'              => $label,
					'singular_name'     => $label,
					'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
					'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
					'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
					'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
					'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
					'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
					'add_new_item'      => sprintf( __( 'Add New %s', 'woocommerce' ), $label ),
					'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label )
				),
				'show_ui'               => false,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				),
				'show_in_nav_menus'     => apply_filters( 'woocommerce_attribute_show_in_nav_menus', false, $name ),
				'rewrite'               => array(
				'slug'         => (empty( $permalinks['attribute_base']) ? '' : trailingslashit($permalinks['attribute_base'])).sanitize_title($name),
				'with_front'   => false,
				'hierarchical' => true
				),
			)
			);
			return true;
		}
	}	
	
	
	/**
	 * Check If WOOCOMMERCE Custom Attribute Exist
	 * @param unknown $name
	 * @return boolean
	 */
	public function attribute_exist($name) {
		
		$taxonomy_exists = taxonomy_exists( wc_attribute_taxonomy_name( $name ) );
		if ($taxonomy_exists ) {
			return true;
			 
		}
		return false;
	}
	
	/**
	 * Just A Simple HOOK Function
	 * @param unknown $json
	 */
	private function get_all_options_list($json){
		foreach($json as $product){
			if(count($product['variants']) > 1){
				foreach($product['variants'] as $var){
					if($var['option1'] !==  null){
						var_dump($var['option1']);
					}
					if($var['option2'] !==  null){
						var_dump($var['option2']);
					}
					if($var['option3'] !==  null){
						var_dump($var['option3']);
					}
				}
			}
				
			#exit();
			}
	
		}
	
	
	
	
	
} 

?>
