<?php

/*  Skynet Tracking API created by Afif Zafri.
    Tracking details are fetched directly from Skynet tracking website,
    parse the content, and return JSON formatted string.
    Please note that this is not the official API, this is actually just a "hack",
    or workaround for implementing Skynet tracking feature in other project.
    Usage: http://site.com/api.php?trackingNo=CODE , where CODE is your tracking number
*/

header("Access-Control-Allow-Origin: *"); # enable CORS

if(isset($_GET['trackingNo']))
{
	$trackingNo = $_GET['trackingNo']; # put your poslaju tracking number here

	$url = "http://www.skynet.com.my/track"; # poslaju update their website with ssl on 2018

	# store post data into array (poslaju website only receive the tracking no with POST, not GET. So we need to POST data)
	$postdata = http_build_query(
			array(
					'hawbNoList' => $trackingNo,
			)
	);

	# use cURL instead of file_get_contents(), this is because on some server, file_get_contents() cannot be used
	# cURL also have more options and customizable
	$ch = curl_init(); # initialize curl object
	curl_setopt($ch, CURLOPT_URL, $url); # set url
	curl_setopt($ch, CURLOPT_POST, 1); # set option for POST data
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); # set post data array
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # receive server response
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); # tell cURL to accept an SSL certificate on the host server
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # tell cURL to graciously accept an SSL certificate on the target server
	$result = curl_exec($ch); # execute curl, fetch webpage content
	$httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE); # receive http response status
	$errormsg = (curl_error($ch)) ? curl_error($ch) : "No error"; # catch error message
	curl_close($ch);  # close curl

	$trackres = array();
	$trackres['http_code'] = $httpstatus; # set http response code into the array

	# use DOMDocument to parse HTML
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dom->loadHTML($result);
	libxml_clear_errors();

	$trackDetails = $dom->getElementById('trackDetails');

	if($trackDetails != null) # check if there is records found or not
	{
		$tables = $trackDetails->getElementsByTagName('table');
		$table = $tables[0];
		$rows = $table->getElementsByTagName('tr');

		$date = "";
		$i = 0;

		foreach ($rows as $row) {

			// set current ad object as new DOMDocument object so we can parse it
	    $newDom = new DOMDocument();
	    $cloned = $row->cloneNode(TRUE);
	    $newDom->appendChild($newDom->importNode($cloned, True));
			# use xpath to query to find elements with certain class or id
	    $xpath = new DOMXPath($newDom);

			// ----- Get row that contain Date -----
			$dateTrackDiv = $xpath->query("//*[contains(@class, 'dateTrackDiv')]");

			if($dateTrackDiv->length > 0) {
				$date = $dateTrackDiv[0]->nodeValue; // update the date variable
			}

			// ----- Get table that contains the time, process, and location -----
	    $trackItemLeft = $xpath->query("//*[contains(@class, 'trackItemLeft')]");
			$trackItemFont = $xpath->query("//*[contains(@class, 'trackItemFont')]");

			if($trackItemLeft->length > 0 && $trackItemFont->length > 0) {
					// ----- GET DATE -----
					$trackres['data'][$i]['date'] = $date;

					// ----- GET TIME -----
					$trackres['data'][$i]['time'] = $trackItemLeft[0]->nodeValue;

					// ----- GET PROCESS & LOCATION -----
					$processTable = $newDom->getElementsByTagName('table');
					$detailTable = $processTable[0]->getElementsByTagName('table');
					$detailColumn = $detailTable[0]->getElementsByTagName('td');
					$trackres['data'][$i]['process'] = $detailColumn[0]->nodeValue;
					$trackres['data'][$i]['location'] = $detailColumn[1]->nodeValue;

					$i++;
			}
		}
	}
	else
	{
		$trackres['message'] = "No Record Found"; # return record not found if number of row < 0
    # since no record found, no need to parse the html furthermore
	}

	# add project info into the array
  $trackres['info']['creator'] = "Afif Zafri (afzafri)";
  $trackres['info']['project_page'] = "https://github.com/afzafri/Skynet-Tracking-API";
  $trackres['info']['date_updated'] =  "15/01/2020";

	# output/display the JSON formatted string
  echo json_encode($trackres);

	//
	// # parse the tracking table, get only the good stuff, and store into and associative array
	// $trackres = array();

	//
	// if(count($tr[0]) > 0) # check if there is records found or not
	// {
	// 	$trackres['message'] = "Record Found"; # return record found if number of row > 0
	//
	// 	for($i=0;$i<count($tr[0]);$i++)
	// 	{
	// 		# check if the string contains the date
	// 		if(strpos($tr[0][$i], '<tact>') === false)
	// 		{
	// 			# increase the index when we found string with date
	// 			$j++;
	// 		}
	//
	// 		# check if the string not contains the date
	// 		if(strpos($tr[0][$i], '<tact>') !== false)
	// 		{
	// 			# parse the table by column <td>
	// 	        $tdpatern = "#<td>(.*?)</td>#";
	// 	        preg_match_all($tdpatern, $tr[0][$i], $td);
	//
	// 	        # store into variable, strip_tags is for removing html tags
	//             $process = strip_tags($td[0][0]);
	//             $time = strip_tags($td[0][1]);
	//             $location = strip_tags($td[0][2]);
	//             $date = $dateArray[$j];
	//
	//             # store into associative array
	//             $trackres['data'][$i]['date'] = $date;
	//             $trackres['data'][$i]['time'] = $time;
	//             $trackres['data'][$i]['process'] = $process;
	//             $trackres['data'][$i]['location'] = $location;
	// 		}
	// 	}
	// 	# rearrange the array index, make it start from 0
	// 	$trackres['data'] = array_values($trackres['data']);
	// }
	// else
	// {
	// 	$trackres['message'] = "No Record Found"; # return record not found if number of row < 0
  //       # since no record found, no need to parse the html furthermore
	// }
	//
	// # add project info into the array
  //   $trackres['info']['creator'] = "Afif Zafri (afzafri)";
  //   $trackres['info']['project_page'] = "https://github.com/afzafri/Skynet-Tracking-API";
  //   $trackres['info']['date_updated'] =  "21/12/2016";
	//
	// # output/display the JSON formatted string
  //   echo json_encode($trackres);
}

?>
