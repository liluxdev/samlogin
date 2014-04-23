<?php

/***  DOCUMENTATION LAYER

	Array Smart Dump Library

	Last Update: Mar 2007
	Author: Tom at klenwell@gmail.com

	FUNCTIONS
		kw_tidy_array($ARRAY, $name='ARRAY')
		array_smart_dump($ARRAY, $name='ARRAY')


  NOTES
		These two functions can be used to write an array to file that will syntactically
		correct and therefore reusuable without further tweaking by PHP.  The effect is
		similar to print_r, with the advantage that it is syntactically sound, meaning
		you can write it to file and then load the array by simply calling the
		file.

		This is useful for the kind of tabular data that is normally kept in a database,
		but remains static over long periods of time.

______________________________________________________________________________*/

/** ensure always same order */
function samlogin_recursive_ksort(&$array) {
   foreach ($array as &$value) {
      if (is_array($value)) samlogin_recursive_ksort($value);
   }
   return ksort($array,SORT_STRING);
}

// kw_tidy_array
/*____________________________________________________________________________*/
function kw_tidy_array($ARRAY, $name='ARRAY')
{
// *** DATA

	// static
	static $drilled_down = 0;

	// internal
	$nl = "\n";
	$nt = "\t";

	// return
	$output = '';


// *** MANIPULATE

	$tab_prt = ''; $_tabx = $drilled_down; while ( $_tabx > 0 ) { $tab_prt .= $nt; $_tabx--; }
	$top_key = ( is_numeric($name) ) ? $name : "'" . str_replace("'", "\'", $name) . "'";
	$output .= $tab_prt . $top_key . ' => ' . "array{$nl}{$tab_prt}({$nl}";
	$drilled_down++;

	while ( list ( $key, $mixed ) = each( $ARRAY ) )
	{
		if ( is_array($mixed) )
		{
			$output .= kw_tidy_array($mixed, $key);
		}
		else
		{
			$tab_prt = ''; $_tabx = $drilled_down; while ( $_tabx > 0 ) { $tab_prt .= $nt; $_tabx--; }
			$key_prt = ( is_numeric($key) ) ? $key : "'" . str_replace("'", "\'", $key) . "'";
			$val_prt = ( is_string($mixed) ) ? "'" . str_replace("'", "\'", $mixed) . "'" : "$mixed";
			if ($val_prt==null){
                            $val_prt="null";
                        }
                        if ($mixed===false){
                           $val_prt="false";
                        }
                        if($mixed===true ){
                            $val_prt="true";
                        }
                        $output .= $tab_prt . $key_prt . ' => ' . $val_prt . ',' . $nl;
		}
	}
	$drilled_down--;

	$tab_prt = ''; $_tabx = $drilled_down; while ( $_tabx > 0 ) { $tab_prt .= $nt; $_tabx--; }
	$output .= $tab_prt . '),' . $nl;


// *** RETURN

	return $output;

}
/*____________________________________________________________________________*/


// array_smart_dump
/*____________________________________________________________________________*/
function array_smart_dump($ARRAY, $name='ARRAY')
{
// *** DATA
samlogin_recursive_ksort($ARRAY);
	// internal
	$nl = "\n";
	$nt = "\t";

	// return
	$output = '';


// *** MANIPULATE

	$rough = kw_tidy_array($ARRAY, $name);
	$rough = preg_replace('%^[^\']*\'([^\']*)\'[^=]*=>%i', '\$$1 = ', $rough, 1);
	$rough = substr($rough,0,strrpos($rough, ','));

	$output = "{$rough};";


// *** RETURN

	return $output;

} // end Fx


?>
