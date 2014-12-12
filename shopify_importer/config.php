<?php
/*=======================================
 * General Settings 
=========================================*/
define('instant_update',true);
define('dataFOLDER',base_dir.'data/');




/*=======================================
 * Shopify Settings
========================================*/
define('site_url','https://cakesquarechennai.myshopify.com/');
define('api_key','1e7dd451c59733590549c701766cde45');
define('api_pass','2b479ec35353de1d046096ae717f5a50');
define('product_fetch_limit','25');
define('collections_fetch_limit','25');


/*======================================
 * Shopify URL Settings
=======================================*/
define('product_count_url','admin/products/count.json');
define('product_json_url','admin/products.json');

define('collections_count_url','admin/custom_collections/count.json');
define('collections_json_url','admin/custom_collections.json'); 



/*======================================
 * File Save Settings
========================================*/
define('product_count_file','productsCOUNT.json');
define('product_json_file','product_json{$count}.json');
define('collections_count_file','collectionsCOUNT.json');
define('collections_json_file','collections_json{$count}.json');
$messageLogs = array(); 


?>
