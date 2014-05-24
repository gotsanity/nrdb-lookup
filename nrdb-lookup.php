<?

/*
Plugin Name: Netrunnerdb.com Lookup
Plugin URI: http://www.projectmulligan.com/netrunnerdb-lookups-for-wordpress/
Description: A shortcode set to lookup named cards from netrunnerdb.com and display their cards on mouseover.
Version: 0.4 BETA
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
		// start output buffering so we can return the whole decklist in one go
		ob_start();	

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
		$identity = nrdb_card(nrdb_ident($deck['cards']));

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

		print "<div class='nrdb-decklist nrdb-embed-box'>";
		print "<div class='nrdb-decklist-identity alignright'>";
		print "<a href='$identity[url]'><img class='alignright nrdb-embed-small' src='http://netrunnerdb.com$identity[imagesrc]' data-nrdb='http://netrunnerdb.com$identity[imagesrc]' /></a>";
		print "</div>";
		print "<div class='nrdb-decklist-name'><h2 class='nrdb-decklist-title'>".$deck['name']."</h2></div>";
		print "<div class='nrdb-decklist-name'><h4 class='nrdb-decklist-meta'>Submitted by: ".$deck['username']." on ".date("F j, Y", strtotime($deck['creation']))."</h4></div>";
		print "<div class='nrdb-decklist-counts'><p class='nrdb-decklist-count'>".count_cards($deck['cards'])." cards (min ".$identity['minimumdecksize'].")</p></div>";
		print "<div class='nrdb-decklist-counts'><p class='nrdb-decklist-count'>".count_influence($deck['cards'], $identity)." influence spent (max ".$identity['influencelimit'].")</p></div>";
		print "<div class='nrdb-decklist-cards'>";
		
		// print out array of cards
		$prev = "jawa";
		$subtype = "jarjar";
		foreach ($deck['cards'] as $card => $value) {
			if ($value['type'] != $prev && $value['type'] != "Identity") {
				if ($prev != "jawa" || $prev != "ICE") {
					print "</ul>";
				}
				
				if ($value['type'] != "ICE" && $value['type'] != "Program") {
					$prev = $value['type'];
					print "<h4 class='nrdb-decklist-category'>".$value['type']."</h4><ul class='nrdb-decklist-card-type'>";
				}
			}
			if ($value['code'] != $identity['code'] && $value['type'] != "ICE" && $value['type'] != "Program") {
				print "<li>".$value['qty']."x <a href='".$value['url']."' data-nrdb='http://netrunnerdb.com".$value['imagesrc']."'>".$value['title']."</a>";
				print_influence($value, $identity);
				print "</li>";
			}
		}
		print "</ul>";
		
		foreach ($deck['cards'] as $key => $card) {
			if ($card['type'] == "ICE") {
				if (strpos($card['subtype'], "Sentry - Code Gate - Barrier") !== false) {
					$ice[$key] = $card;
				} elseif (strpos($card['subtype'], "Barrier") !== false) {
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
			} elseif ($card['type'] == "Program") {
				if (strpos($card['subtype'], "Icebreaker") !== false) {
					$icebreakers[$key] = $card;
				} else {
					$programs[$key] = $card;
				}
			}
		}
		
		print_category($barrier, $identity, "ICE: Barrier");		
		print_category($code_gate, $identity, "ICE: Code Gate");
		print_category($sentry, $identity, "ICE: Sentry");
		print_category($trap, $identity, "ICE: Trap");
		print_category($ice, $identity, "ICE: Other");
		print_category($icebreakers, $identity, "Icebreaker");
		print_category($programs, $identity, "Programs");
		
		print "</ul></div>";
		print "<div class='nrdb-decklist-description'>".$deck['description']."</div>";
		print "</div>";
		//$output = "<pre>".print_r($deck, true)."</pre>";
		return ob_get_clean();
	}
	// return a match from the array of matched cards formatted as a link.
	return $output;
}

add_shortcode("nrdb", "nrdb_function");

// Functions
function nrdb_load_js(){
    wp_enqueue_script( 'nrdb-lookup_js', plugins_url( '/js/nrdb-lookup.js', __FILE__ ), array('jquery') );
    wp_register_style('nrdb-lookup_style', '/wp-content/plugins/nrdb-lookup/style.css');
		wp_enqueue_style( 'nrdb-lookup_style', '/wp-content/plugins/nrdb-lookup/style.css', '0.4' );
}
add_action('wp_enqueue_scripts', 'nrdb_load_js', '9999');

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
		if ($ident['title'] == "The Professor: Keeper of Knowledge" && $card['type'] == "Program") {
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

function count_cards($cards) {
	$n = 0;
	foreach ($cards as $k => $v) {
		if ($v['type'] != "Identity") {
			$n = $n + $v['qty'];
		}
	}
	return $n;
}

function count_influence($cards, $ident) {
	$n = 0;
	foreach ($cards as $k => $v) {
		if ($ident['title'] == "The Professor: Keeper of Knowledge") {
			if ($v['faction'] != $ident['faction'] && $v['factioncost'] > 0) {
				if ($v['type'] == "Program") {
					if ($v['qty'] > 1) {
						$n = $n + ($v['factioncost'] * ($v['qty'] - 1));
					}
				} else {
					$n = ($n + ($v['factioncost']*$v['qty']));
				}
			}
		} else {
			if ($v['faction'] != $ident['faction'] && $v['factioncost'] > 0) {
				$n = ($n + ($v['factioncost']*$v['qty']));
			}
		}
	}
	return $n;
}

function print_category($cards, $identity, $title) {
	if (!empty($cards)) {
		print "<h4 class='nrdb-decklist-category'>$title</h4><ul class='nrdb-decklist-card-type'>";
		foreach ($cards as $key => $card) {
			print "<li>".$card['qty']."x <a href='".$card['url']."' data-nrdb='http://netrunnerdb.com".$card['imagesrc']."'>".$card['title']."</a>";
			print_influence($card, $identity);
			print "</li>";
		}
		print "</ul>";
	}
}

function register_button( $buttons ) {
	array_push($buttons, "nrdbmouseover", "nrdbembed", "nrdbdecklist");
	return $buttons;
}

function add_plugin( $plugin_array ) {
   $plugin_array['nrdbmouseover'] = plugins_url('nrdb-lookup') . '/js/mouseover.js';
   $plugin_array['nrdbembed'] = plugins_url('nrdb-lookup') . '/js/embed.js';
   $plugin_array['nrdbdecklist'] = plugins_url('nrdb-lookup') . '/js/decklist.js';
   return $plugin_array;
}

function nrdb_lookup_button() {

   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
      return;
   }

   if ( get_user_option('rich_editing') == 'true' ) {
      add_filter( 'mce_external_plugins', 'add_plugin' );
      add_filter( 'mce_buttons', 'register_button' );
   }

}

add_action('init', 'nrdb_lookup_button');
?>
