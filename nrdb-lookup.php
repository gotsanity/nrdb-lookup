<?

/*
Plugin Name: Netrunnerdb.com Lookup
Plugin URI: http://www.reallyeffective.co.uk/knowledge-base
Description: A shortcode set to lookup named cards from netrunnerdb.com and display their cards on mouseover.
Version: 0.3 BETA
Author: Jesse Harlan
Author URI: http://www.insidiousdesigns.net
*/

/*
Netrunnerdb.com Lookup (Wordpress Plugin)
Copyright (C) 2014 Jesse Harlan
Contact me at http://www.insidiousdesigns.net

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function nrdb_function($atts, $content = null) {
	// get file contents and decode
	$dir = plugin_dir_path( __FILE__ );
	$lines_coded = file_get_contents($dir."assets/cards.txt");
	$lines = json_decode($lines_coded);

	if(date("Y-m-d") > date("Y-m-d", filemtime($dir."assets/cards.txt"))) {
		// update card assets
		set_time_limit(0);
		$fp = fopen ($dir . 'assets/cards.txt', 'w+');//This is the file where we save the    information
		$ch = curl_init("http://netrunnerdb.com/api/cards/");
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FILE, $fp); // write curl response to file
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch); // get curl response
		curl_close($ch);
		fclose($fp);
		$lines_coded = file_get_contents($dir."assets/cards.txt");
		$lines = json_decode($lines_coded);
	}

	// check for decklist $atts and process a decklist if present
	if (empty($atts['decklist'])) {
		// process embed and mouseover
		// setup needle to search for in the haystack
		$needle = array($content);

		// walk through needle aray and find matches
		foreach ($needle as $k => $v) {
			$v = normalize($v);
			$matches[] = find_matches($lines, $v);
		}

		// set default size for embeded images	
		if (!$atts['size']) {
			$nrdb_size = "nrdb-embed-small";
		} else {
			$nrdb_size = "nrdb-embed-".$atts['size'];
		}

		// run through output types and construct output
		if (!empty($atts['embed'])) {
			$nrdb_align = "align".$atts['embed'];
			$output = "<a href='$matches[0]'><img class='$nrdb_align $nrdb_size nrdb-embed-box' src='$matches[0]' data-nrdb='$matches[0]' /></a>";
		}
	
		if (!$atts['embed']) {
			$output = "<a href='$matches[0]' data-nrdb='$matches[0]'>$content</a>";
		}
	} elseif (!empty($atts['decklist'])) {
		// process decklist

		// create curl resource
    $ch = curl_init();
    // set url
    curl_setopt($ch, CURLOPT_URL, "http://netrunnerdb.com/api/decklist/".$atts['decklist']."?mode=embed");
    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // $output contains the output string
    $decklist = curl_exec($ch);
    // close curl resource to free up system resources
    curl_close($ch);

		$deck = json_decode($decklist, true);
		
		print "<div class='nrdb-decklist clearfix nrdb-embed-box aligncenter'>";
		print "<div class='clearfix'>";
		print "<div class='nrdb-decklist-name'><h2>".$deck['name']."<h2></div>";
		print "<div class='nrdb-decklist-name'><h4>Submitted by: ".$deck['username']." on ".date("F j, Y", strtotime($deck['creation']))."<h4></div>";
		print "</div>";
		print "<div class='nrdb-decklist-identity alignright'>";
		$identity = nrdb_card(nrdb_ident($deck['cards']));
		print "<a href='$identity[url]'><img class='alignright nrdb-embed-small' src='http://netrunnerdb.com$identity[imagesrc]' data-nrdb='http://netrunnerdb.com$identity[imagesrc]' /></a>";
		print "</div>";
		print "<div class='nrdb-decklist-cards'>";
		
		// load and sort array of cards by type
		foreach ($deck['cards'] as $card => $qty) {
			// fill card details into deck array
			$current = nrdb_card($card);
			$current['qty'] = $qty;
			$deck['cards'][$card] = $current;
			
			// sort array
			$types = array();
			$names = array();
			$subtype = array();
			foreach ($deck['cards'] as $k => $v) {
					$types[$k] = $v['type'];
					$names[$k] = $v['title'];
			}
			array_multisort($types, SORT_ASC, $names, SORT_ASC, $deck['cards']);
		}
		
		// print out array of cards
		$prev = "jawa";
		$subtype = "jarjar";
		foreach ($deck['cards'] as $card => $value) {
			if ($value['type'] != $prev && $value['type'] != "Identity") {
				if ($prev != "jawa" || $prev != "ICE") {
					print "</ul>";
				}
				
				if ($value['type'] != "ICE") {
					$prev = $value['type'];
					print "<h4>".$value['type']."</h4><ul class='nrdb-decklist-card-type'>";
				}
			}
			if ($value['code'] != $identity['code'] && $value['type'] != "ICE") {
				print "<li>".$value['qty']."x <a href='".$value['url']."' data-nrdb='http://netrunnerdb.com".$value['imagesrc']."'>".$value['title']."</a>";
				print_influence($value, $identity);
				print "</li>";
			}
		}
		print "</ul>";
		
		foreach ($deck['cards'] as $key => $card) {
			if ($card['type'] == "ICE") {
				if (strpos($card['subtype'], "Barrier") !== false) {
					$barrier[$key] = $card;
				} elseif (strpos($card['subtype'], "Code Gate") !== false) {
					$code_gate[$key] = $card;
				} elseif (strpos($card['subtype'], "Sentry") !== false) {
					$sentry[$key] = $card;
				} elseif (strpos($card['subtype'], "Trap") !== false) {
					$trap[$key] = $card;
				} else {
					$ice[$key] = $card;
				}
			}
		}
		
		if (!empty($barrier)) {
			print "<h4>ICE: Barrier</h4><ul class='nrdb-decklist-card-type'>";
			foreach ($barrier as $key => $card) {
				print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			}
			print "</ul>";
		}

		if (!empty($code_gate)) {
			print "<h4>ICE: Code Gate</h4><ul class='nrdb-decklist-card-type'>";
			foreach ($code_gate as $key => $card) {
				print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			}
			print "</ul>";
		}

		if (!empty($sentry)) {
			print "<h4>ICE: Sentry</h4><ul class='nrdb-decklist-card-type'>";
			foreach ($sentry as $key => $card) {
				print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			}
			print "</ul>";
		}

		if (!empty($trap)) {
			print "<h4>ICE: Trap</h4><ul class='nrdb-decklist-card-type'>";
			foreach ($trap as $key => $card) {
				print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			}
			print "</ul>";
		}

		if (!empty($ice)) {
			print "<h4>ICE: Other</h4><ul class='nrdb-decklist-card-type'>";
			foreach ($ice as $key => $card) {
				print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			}
			print "</ul>";
		}
		
		print "</ul></div>";
		print "<div class='nrdb-decklist-description'>".$deck['description']."</div>";
		print "</div>";
		$output = "<pre>".print_r($deck, true)."</pre>";
		return $output;
	}
	// return a match from the array of matched cards formatted as a link.
	return $output;
}

add_shortcode("nrdb", "nrdb_function");

// Functions
function nrdb_load_js(){
		wp_enqueue_style( 'nrdb-lookup_style', '/wp-content/plugins/nrdb-lookup/style.css' );
    wp_enqueue_script( 'nrdb-lookup_js', plugins_url( '/js/nrdb-lookup.js', __FILE__ ), array('jquery') );
}
add_action('wp_enqueue_scripts', 'nrdb_load_js');

function find_matches($haystack = array(), $needle) {
	// search through each card in the haystack and match against the needle.
	foreach ($haystack as $key => $object) {
		$object->title = normalize($object->title);
		$lev = (levenshtein($object->title, $needle) / (strlen($object->title) + strlen($needle)) * 100);
		$matches[] = array(
			'title' => $object->title,
			'lev' => $lev,
			'imagesrc' => $object->imagesrc
		);
	}
	return closest_match($matches);
}

/*
function print_match($image) {
	// find the closest match and get its image address
	if ($image == false) {
		print "<p>No image found!</p>";
	} else {
		echo "<img src='$image' />";
	}
} */

function closest_match($matches) {
	$sorted = array();
	foreach ($matches as $k => $v) {
		if ($v['lev'] <= 26) {
			$sorted[$k] = $v['lev'];
		}
	}
	
	// if array is empty then return false
	if (count($sorted) < 1) {
		return false;
	}

	// sort the array
	asort($sorted);

	$i = 0;
	do {
		foreach ($sorted as $k => $v) {
			$return = "http://netrunnerdb.com".$matches[$k]['imagesrc'];
			break;
		}
		$i++;
	} while ($i < 1);
	return $return;
}

function nrdb_card($id) {
	$dir = plugin_dir_path( __FILE__ );
	$lines_coded = file_get_contents($dir."assets/cards.txt");
	$lines = json_decode($lines_coded, true);
	foreach ($lines as $key => $value) {
		if ($value['code'] == $id) {
			$me = $lines[$key];
			return $me;
		}
	}
}

function nrdb_ident($cards) {
	$dir = plugin_dir_path( __FILE__ );
	$lines_coded = file_get_contents($dir."assets/cards.txt");
	$lines = json_decode($lines_coded, true);
	foreach ($cards as $k => $v) {
		foreach ($lines as $key => $value) {
			if ($k == $value['code'] && strtolower($value['type']) == "identity") {
				return $value['code'];
			}
		}
	}
	return null;
}

function normalize($string) {
	// strip special characters and convert to lower case
	$string = mb_strtolower($string, 'utf-8');
	$string = mb_ereg_replace("[^A-Za-z0-9\w ]", "", $string);
	return $string;
}

function print_influence($card, $ident) {
	$cost = check_influence($card, $ident);
	
	print " <span class='nrdb-influence'>";
	while ($cost > 0) {
		print "&bull;";
		$cost--;
	}
	print "</span>";
}

function check_influence($card, $ident) {
	if ($card['faction'] != $ident['faction']) {
		if ($ident['title'] == "The Professor Keeper of Knowledge") {
			if ($card['factioncost'] > 0 && $card['qty'] > 1) {
				return ($card['factioncost'] * ($card['qty'] - 1));
			} else {
				return;
			}
		} elseif ($card['factioncost'] != 0) {
			return $card['factioncost'] * $card['qty'];
		} else {
			return;
		}
	}
}

?>


