<?php
/*
JLFunctions String Class
Copyright (c)2009-2012 John Lamansky
*/

class lat_string {
	
	/**
	 * Returns whether or not a given string starts with a given substring.
	 * 
	 * @param string $str The "haystack" string.
	 * @param string $sub The "needle" string.
	 * @return bool Whether or not $str starts with $sub.
	 */
	static function startswith( $str, $sub ) {
	   return ( substr( $str, 0, strlen( $sub ) ) === $sub );
	}
	
	/**
	 * Returns whether or not a given string ends with a given substring.
	 * 
	 * @param string $str The "haystack" string.
	 * @param string $sub The "needle" string.
	 * @return bool Whether or not $str ends with $sub.
	 */
	static function endswith( $str, $sub ) {
	   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
	}
	
	/**
	 * Makes a string start with a given string if it doesn't already.
	 * 
	 * @param string $str The string that should start with $start
	 * @param string $start
	 * 
	 * @return string The string with $start at the beginning
	 */
	static function startwith( $str, $start ) {
		if (!lat_string::startswith($str, $start))
			return $start.$str;
		else
			return $str;
	}
	
	/**
	 * Makes a string end with a given string if it doesn't already.
	 * 
	 * @param string $str The string that should end with $end
	 * @param string $end
	 * 
	 * @return string The string with $end at the end
	 */
	static function endwith( $str, $end ) {
		if (!lat_string::endswith($str, $end))
			return $str.$end;
		else
			return $str;
	}
	
	/**
	 * Checks whether or not $str has $sub in it somewhere.
	 * 
	 * @param string $str
	 * @param string $sub
	 * @return bool
	 */
	static function has($str, $sub) {
		return (strpos($str, $sub) !== false);
	}
	
	/**
	 * Case-insensitively checks whether or not $str has $sub in it somewhere.
	 * 
	 * @param string $str
	 * @param string $sub
	 * @return bool
	 */
	static function ihas($str, $sub) {
		$str = strtolower($str);
		$sub = strtolower($sub);
		return (strpos($str, $sub) !== false);
	}
	
	/**
	 * Truncates a string if it is longer than a given length.
	 * 
	 * @param string $str The string to possibly truncate.
	 * @param int $maxlen The desired maximum length of the string.
	 * @param string $truncate The string that should be added to the end of a truncated string.
	 * @return string
	 */
	static function truncate( $str, $maxlen, $truncate = '...', $maintain_words=false ) {
		
		if ( strlen($str) > $maxlen ) {
			$str = substr( $str, 0, $maxlen - strlen($truncate) );
			if ($maintain_words) $str = preg_replace('/ ([^ ]+)$/', '', $str);
			$str .= $truncate;
		}
			
		return $str;
	}
	
	/**
	 * Returns the contents of $str up to, but not including, the first instance of $sub.
	 * 
	 * @param string $str
	 * @param string $sub
	 * @return string
	 */
	static function upto($str, $sub) {
		$end = strpos($str, $sub);
		if ($end === false)
			return $str;
		else
			return substr($str, 0, $end);
	}
	
	/**
	 * Joins strings into a natural-language list (e.g. "A and B" or "A, B, and C")
	 * Can be internationalized with gettext or the sl_lang_implode filter.
	 * 
	 * @param array $items The strings (or objects with $var child strings) to join.
	 * @param string|false $var The name of the items' object variables whose values should be imploded into a list.
		If false, the items themselves will be used.
	 * @param bool $ucwords Whether or not to capitalize the first letter of every word in the list.
	 * @return string|array The items in a natural-language list.
	 */
	static function nl_implode($items, $var=false, $ucwords=false) {
		
		if (is_array($items) ) {
			
			if (strlen($var)) {
				$_items = array();
				foreach ($items as $item) $_items[] = $item->$var;
				$items = $_items;
			}
			
			if ($ucwords) $items = array_map('ucwords', $items);
			
			switch (count($items)) {
				case 0: $list = ''; break;
				case 1: $list = $items[0]; break;
				case 2: $list = sprintf(__('%s and %s', 'seolat-tool-plus'), $items[0], $items[1]); break;
				default:
					$last = array_pop($items);
					$list = implode(__(', ', 'seolat-tool-plus'), $items);
					$list = sprintf(__('%s, and %s', 'seolat-tool-plus'), $list, $last);
					break;
			}
			
			return apply_filters('sl_lang_implode', $list, $items);
		}

		return $items;
	}
	
	/**
	 * If the given string ends with the given suffix, the suffix is removed.
	 * 
	 * @param string $str The string from which the provided suffix should be trimmed if located.
	 * @param string $totrim The suffix that should be trimmed if found.
	 * @return string The possibly-trimmed string.
	 */
	static function rtrim_str($str, $totrim) {
		if (strlen($str) > strlen($totrim) && lat_string::endswith($str, $totrim))
			return substr($str, 0, -strlen($totrim));
		
		return $str;
	}
	
	/**
	 * If the given string ends with the given suffix or any portion thereof, the suffix or suffix portion is removed.
	 * 
	 * @param string $str The string from which the provided suffix should be trimmed if located.
	 * @param string $totrim The suffix that should be trimmed if it or a portion of it is found.
	 * @return string The possibly-trimmed string.
	 */
	static function rtrim_substr($str, $totrim) {
		for ($i = strlen($totrim); $i > 0; $i--) {
			$totrimsub = substr($totrim, 0, $i);
			if (lat_string::endswith($str, $totrimsub))
				return lat_string::rtrim_str($str, $totrimsub);
		}
		
		return $str;
	}
	
	/**
	 * If the given string begins with the given prefix, the prefix is removed.
	 * 
	 * @param string $str The string of which the provided prefix should be trimmed if located.
	 * @param string $totrim The prefix that should be trimmed if found.
	 * @return string The possibly-trimmed string.
	 */
	static function ltrim_str($str, $totrim) {
		if (strlen($str) > strlen($totrim) && lat_string::startswith($str, $totrim))
			return substr($str, strlen($totrim));
		
		return $str;
	}
	
	static function batch_replace($search, $replace, $subjects) {
		$subjects = array_unique((array)$subjects);
		$results = array();
		foreach ($subjects as $subject) {
			$results[$subject] = str_replace($search, $replace, $subject);
		}
		return $results;
	}
	
	static function to_int($str) {
		return intval(lat_string::preg_filter('0-9', strval($str)));
	}
	
	static function preg_filter($filter, $str) {
		$filter = str_replace('/', '\\/', $filter);
		return preg_replace("/[^{$filter}]/", '', $str);
	}
	
	static function preg_escape($str, $delim='%') {
		$chars = "\ ^ . $ | ( ) [ ] * + ? { } , ".$delim;
		$chars = explode(' ', $chars);
		foreach ($chars as $char)
			$str = str_replace($char, '\\'.$char, $str);
		return $str;
	}
	
	static function htmlsafe_str_replace($search, $replace, $subject, $limit, &$count, $exclude_tags = false) {
		$search = lat_string::preg_escape($search, '');
		return lat_string::htmlsafe_preg_replace($search, $replace, $subject, $limit, $count, $exclude_tags);
	}
	
	static function htmlsafe_preg_replace($search, $replace, $subject, $limit, &$count, $exclude_tags = false) {
		
		if (!$exclude_tags || !is_array($exclude_tags)) $exclude_tags = array('a', 'pre', 'code', 'kbd');
		if (count($exclude_tags) > 1)
			$exclude_tags = lat_string::preg_filter('a-z0-9|', implode('|', $exclude_tags));
		else
			$exclude_tags = array_shift($exclude_tags);
		
		$search = str_replace('/', '\/', $search);
		
		//Based off of regex from
		//http://stackoverflow.com/questions/3013164/regex-to-replace-a-string-in-html-but-not-within-a-link-or-heading
		switch (mb_detect_encoding($search)) {
			case 'UTF-8':		
			$search_regex = "/($search)(?!(?:(?!<\/?(?:$exclude_tags).*?>).)*+<\/(?:$exclude_tags).*?>)(?![^<>]*+>)/imsu";
			break;
			default:			
			$search_regex = "/\b($search)\b(?!(?:(?!<\/?(?:$exclude_tags).*?>).)*+<\/(?:$exclude_tags).*?>)(?![^<>]*+>)/imsu";
			break;
		}		
		return preg_replace($search_regex, $replace, $subject, $limit, $count);
	}
	
	static function tclcwords($str) {
		$words = explode(' ', $str);
		$new_words = array();
		foreach ($words as $word) {
			if (strtolower($word) == $word)
				$new_words[] = ucwords($word);
			else
				$new_words[] = $word;
		}
		return implode(' ', $new_words);
	}
	
	static function camel_case($string) {
		$string = strtolower($string);
		$string = preg_replace('@[^a-z0-9]@', ' ', $string);
		$words = array_filter(explode(' ', $string));
		$first = array_shift($words);
		$words = array_map('ucwords', $words);
		$words = implode('', $words);
		$string = $first . $words;
		return $string;
	}
	
	static function wildcards_to_regex($wcstr) {
		$wcstr = lat_string::preg_escape($wcstr, '@');
		$wcstr = str_replace('\\*', '.*', $wcstr);
		$regex = "@^$wcstr$@i";
		$regex = str_replace(array('@^.*', '.*$@i'), array('@', '@i'), $regex);
		return $regex;
	}
	
	static function tolower($str) {
		if (function_exists('mb_strtolower'))
			return mb_strtolower($str);
		
		return strtolower($str);
	}
}

?>