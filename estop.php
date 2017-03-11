#!/usr/bin/php
<?php
// sudo yum install php-cli

$_ELASTIC_URL='http://localhost:9200';
$_SLEEPTIME=10; // seconds
$_TOPNUM=20;   // top indexes num

////////////////////////////////////////////

echo "Collecting info. Please wait..\n";


function get_indicies(){
	global $_ELASTIC_URL;
	echo "Getting info from Elastic ..\n";
	$url = $_ELASTIC_URL.'/_stats/indexing';
	#$url = '1.txt';
	return json_decode( file_get_contents($url), true );
}


function print_to_table($c1,$c2,$c3,$c4){
	printf(
		  "| %s | %s | %s | %s |\n"
		, str_pad($c1,50," ",STR_PAD_BOTH)
		, str_pad($c2,20)
		, str_pad($c3,20)
		, str_pad($c4,25)
	);
}

function print_table_line($d="-"){ echo str_pad($d, 128 ,$d)."\n"; }


function compare($a,$b){
        if ($a[3] == $b[3]) return 0;
        return ($a[3] < $b[3]) ? 1 : -1;
}


function prepare_data(){
	global $json1, $json2, $rating, $measure_time;
	global $_SLEEPTIME;

	$json1 = (empty($json1)) ? get_indicies() : $json2;  
	echo "Update interval is {$_SLEEPTIME}s\n";
	$measure_time = microtime(true);
	sleep($_SLEEPTIME);
	$json2 = get_indicies();
	$measure_time = microtime(true) - $measure_time;

	echo "Preparing data";
	$rating = array();
	foreach  ($json1['indices'] as $ind_name => $ind_obj ){
		$ind1 = (int)$ind_obj['total']['indexing']['index_total'];
		$ind2 = (int)$json2['indices'][$ind_name]['total']['indexing']['index_total'];
		$rating[] = array( $ind_name, $ind1, $ind2, ( $ind2 - $ind1 )  );
	}

	usort($rating, "compare");
}


function print_screen($extra_text=""){
	global $_TOPNUM, $_SLEEPTIME, $rating,$measure_time;

	system('clear');
	echo "{$extra_text}\n";
	echo "\n\nTop {$_TOPNUM} Hot Indexes\n";
	print_table_line();
	print_to_table('Index name','First measure','Second measure','Records per second');
	print_table_line();
	$i=0;
	foreach ( $rating as $i => $r ){ 
		if($i++ > $_TOPNUM) break;
		print_to_table( $r[0], $r[1], $r[2], ( round( $r[3]/$measure_time, 2 )  ).' rec/s' );
	}
	print_table_line();
}

///

while (true){
	prepare_data();
	print_screen();
}

?>
