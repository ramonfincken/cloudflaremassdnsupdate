<?php
/**

Code by Ramon Fincken


Base code from https://developers.cloudflare.com/api/
this is not OOP written

just set and visit your PHP webserver, it will auto walk each separate domain (zone) untill all are done

I take no reponsibility from any errors or DNS gone wrong.
Only use this script if you *really* know what you are doing. If something goes wrong, it's on you.
*/


/*
needs defines for
TOKEN (string)
SEARCH_IP (string)
REPLACE_IP (string)
*/
require 'config.php';




/* --------------------------------------------------------------------------------------------------- */



function get_dns( $zone_id )  {
	$curl = curl_init();

	curl_setopt_array($curl, [
	  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/".$zone_id."/dns_records",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => [
	    "Authorization: Bearer ".TOKEN,
	    "Content-Type: application/json"
	  ],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  $list = json_decode( $response );
	  
		foreach( $list->result as $item ) {
			if( $item->type === 'A' || $item->type === 'AAAA' ) {
				if( $item->content === SEARCH_IP ) {
					update_dns( $zone_id, $item, REPLACE_IP );
				}
			}
		}		
	}
}

function update_dns( $zone_id, $item, $content ) {
	$curl = curl_init();
	
	$dns_row_id = $item->id;

	curl_setopt_array($curl, [
	  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/".$zone_id."/dns_records/".$dns_row_id,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "PATCH",
	  CURLOPT_POSTFIELDS => "{\n  \"content\": \"".$content."\",\n  \"name\": \"".$item->name."\",\n  \"type\": \"".$item->type."\",\n  \"proxied\": true,\n  \"id\": \"".$dns_row_id."\"\n}",
	  CURLOPT_HTTPHEADER => [
	    "Authorization: Bearer ".TOKEN,
	    "Content-Type: application/json"
	  ],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  $list = json_decode( $response );
	  
		print_r($list);
	}	
}




$curl = curl_init();

if( !isset( $_GET['page']  ) )  {
	$page = 1;
} else {
	$page = intval( $_GET['page'] );
}

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones?per_page=1&page=".$page,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer ".TOKEN,
    "Content-Type: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
	$list = json_decode( $response );
	
	foreach( $list->result as $item ) {
		$zone_id = $item->id;
		echo $item->name;
		get_dns( $zone_id );
		
	}
	
	if( $list->result_info->page < $list->result_info->total_count ) {
		$page++;
		echo '<meta http-equiv="refresh" content="5; URL=\'?page='.$page.'" />';
	} else {
		echo "All done";
	}
	
}
