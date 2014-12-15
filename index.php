<?php

date_default_timezone_set('America/New_York');

$last_month = mktime(0,0,0,date('m')-1, 1, date('Y'));

define('USERNAME', '<username>');
define('PASSWORD', '<password>');
define('MONTH', date('Y-m', $last_month));

$brands = array(
	'sitename1' => 'Title 1',
	'sitename2' => 'Title 2',
);

//minutes in the month
$days_last_month = date("t", $last_month);
$minutes_last_month = $days_last_month * 24 * 60;

$interval = ($days_last_month+date('d'));

foreach ($brands as $docroot => $title) {

	$url = "https://perf-mon.acquia.com/site_downtime.php?stage=mc&period=d&interval=".$interval."&sitename=".$docroot;
	
	$process = curl_init($url);

	curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: text/html', $additionalHeaders));
	curl_setopt($process, CURLOPT_HEADER, 1);
	curl_setopt($process, CURLOPT_USERPWD, USERNAME . ":" . PASSWORD);
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_POST, 1);
	curl_setopt($process, CURLOPT_POSTFIELDS, $payloadName);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	$html = curl_exec($process);
	curl_close($process);

	sleep(1);

	list($waste, $downtime) = explode("Detected as having downtime at: <br><br>", $html);
	
	$occurances = explode('<br>', $downtime);
	
	$minutes = 0;
	
	foreach($occurances as $occurance) {
		
		if(substr($occurance, 0, 7) == MONTH) {
			$minutes++;
		}	
		
	}
	
	$docroot_sla = (100 - ($minutes / $minutes_last_month * 100));
	
	$sla_environments[] = array(
		'title' => $title,
		'link' => $url,
		'docroot' => $docroot,
		'minutes' => $minutes,
		'docroot_sla' => $docroot_sla,
	);
	
}

$output = array();

usort($sla_environments, "sla_sort");
$sla_environments = array_reverse($sla_environments);

foreach($sla_environments as $environment) {
	$output[] = '<tr dir="ltr">';
	$output[] = '<td class="s2">'.$environment['title'].'</td>';
	//$output[] = '<td class="s3"><a href="'.$environment['link'].'" target="_blank">'.$environment['docroot'].'</a></td>';
	$output[] = '<td class="s3">'.$environment['docroot'].'</td>';
	$output[] = '<td class="s4">'.$environment['minutes'].'</td>';
	$output[] = '<td class="s5">'.number_format($environment['docroot_sla'], 2).'%</td>';
	$output[] = '<td class="s6">'.(($environment['docroot_sla'] > 99.95) ? 'Yes' : 'No').'</td>';
	$output[] = '</tr>';
}

// output is designed to be copy/paste into the TAM report as a table.
// pipe this to a file :)

echo implode("\n", $output);
exit;

function sla_sort($a, $b) {
	if ($a['minutes'] == $b['minutes']) {
		return 0;
	}
	return ($a['minutes'] < $b['minutes']) ? -1 : 1;
}
