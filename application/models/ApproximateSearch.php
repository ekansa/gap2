<?php
/*******************************************************************************
 * Copyright (C) 2003-2006 by Jarno Elonen <elonen@iki.fi>
 *
 * This is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1,
 * or (at your option) any later version.
 *
 * This is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with the program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *******************************************************************************/

// A PHP class for approximate string searching of large text masses.
// Usage example:
//
//    $search = new Approximate_Search( $patt, $max_err );
//    if ( $search->too_short_err )
//        $error = "Unable to search - use longer pattern " .
//                 "or reduce error tolerance.";
//
//    while( $text = read_next_page())
//    {
//        $matches = $search->search( $text );
//        while( list($i,) = each($matches))
//          print "Match that ends at $i.\n";
//    }
//
// The code uses initial filtering to sort out possible match
// candidates and then applies a slower character-by-character
// search (search_short()) against them.

class ApproximateSearch
{
    // Searches for $patt, allowing at most $k errors from $text.
    //
    // The last 3 parameters are for optimization only, to avoid the
    // surprisingly slow strlen() and substr() calls:
    //  - $start_index = from which character of $text to start the search
    //  - $max_len = maximum character to search (starting from $start_index)
    //  - $text_strlen =
    //
    // The return value is an array of matches:
    //   Array( [<match-end-index>] => <error>, ... )
    //
    // Note: <error> is generally NOT an exact edit distance but rather a
    // lower bound. This is unfortunate but the routine would be slower if
    // the exact error was calculate along with the matches.
    //
    // The function is based on the non-deterministic automaton simulation
    // algorithm (without bit parallelism optimizations).
    function search_short($patt, $k, $text, $start_index=0, $max_len=-1, $text_strlen=-1)
    {
        if ( $text_strlen < 0 )
            $text_strlen = strlen( $text );

        if ( $max_len < 0 )
            $max_len = $text_strlen;

        $start_index = max( 0, $start_index );
        $n = min( $max_len, $text_strlen-$start_index );
        $m = strlen( $patt );
        $end_index = $start_index + $n;

        // If $text is shorter than $patt, use the built-in
        // levenshtein() instead:
        if ($n < $m)
        {
            $lev = levenshtein(substr($text, $start_index, $n), $patt);
            if ( $lev <= $k )
                return Array( $start_index+$n-1 => $lev );
            else
                return Array();
        }

        $s = Array();
        for ($i=0; $i<$m; $i++)
        {
            $c = $patt{$i};
            if ( isset($s[$c]))
                $s[$c] = min($i, $s[$c]);
            else
                $s[$c] = $i;
        }

        if ( $end_index < $start_index )
            return Array();

        $matches = Array();
        $da = $db = range(0, $m-$k+1);

        $mk = $m-$k;

        for ($t=$start_index; $t<$end_index; $t++)
        {
            $c = $text{$t};
            $in_patt = isset($s[$c]);

            if ($t&1) { $d=&$da; $e=&$db; }
            else { $d=&$db; $e=&$da; }

            for ($i=1; $i<=$mk; $i++)
            {
                $g = min( $k+1, $e[$i]+1, $e[$i+1]+1 );

                // TODO: optimize this with a look-up-table?
                if ( $in_patt )
                    for ($j=$e[$i-1]; ($j<$g && $j<=$mk); $j++)
                        if ( $patt{$i+$j-1} == $c )
                            $g = $j;

                $d[$i] = $g;
            }

            if ( $d[$mk] <= $k )
            {
                $err = $d[$mk];
                $i = min( $t-$err+$k+1, $start_index+$n-1);
                if ( !isset($matches[$i]) || $err < $matches[$i])
                    $matches[$i] = $err;
            }
        }

        unset( $da, $db );
        return $matches;
    }

    function test_short_search()
    {
        $test_text = "Olipa kerran jussi bj&xling ja kolme\n iloista ".
            "jussi bforling:ia mutta ei yhtaan jussi bjorling-nimista laulajaa.";
        $test_patt = "jussi bjorling";
        assert( $this->search_short($test_patt, 4, $test_text) == Array(27=>2, 60=>1, 94=>0));
        assert( $this->search_short($test_patt, 2, $test_text) == Array(27=>2, 60=>1, 94=>0));
        assert( $this->search_short($test_patt, 1, $test_text) == Array(60=>1, 94=>0));
        assert( $this->search_short($test_patt, 0, $test_text) == Array(94=>0));
        assert( $this->search_short("bjorling", 2, $test_text, 19, 7) == Array());
        assert( $this->search_short("bjorling", 2, $test_text, 19, 8) == Array(26=>2));
        assert( $this->search_short("bjorling", 2, $test_text, 20, 8) == Array());
    }


    var $patt, $patt_len, $max_err;
    var $parts, $n_parts, $unique_parts, $max_part_len;
    var $transf_patt;
    var $too_short_err;

    function prepSearch( $pattern, $max_error )
    {
        $this->patt = $pattern;
        $this->patt_len = strlen($this->patt);
        $this->max_err = $max_error;

        //echo "PATTERN: ".$this->patt;


        // Calculate pattern partition size
        $intpartlen = floor($this->patt_len/($this->max_err+2));
        if ($intpartlen < 1)
        {
            $this->too_short_err = True;
            return;
        }
        else $this->too_short_err = False;

        // Partition the pattern for pruning
        $this->parts = Array();
        for ($i=0; $i<$this->patt_len; $i+=$intpartlen)
        {
            if ( $i + $intpartlen*2 > $this->patt_len )
            {
                $this->parts[] = substr( $this->patt, $i );
                break;
            }
            else
                $this->parts[] = substr( $this->patt, $i, $intpartlen );
        }
        $this->n_parts = count($this->parts);

        // The intpartlen test above should have covered this:
        assert( $this->n_parts >= $this->max_err+1 );

        // Find maximum part length
        foreach( $this->parts as $p )
            $this->max_part_len = max( $this->max_part_len, strlen($p));

        // Make a new part array with duplicate strings removed
        
        $this->unique_parts = array_unique($this->parts);

        // Transform the pattern into a low resolution pruning string
        // by replacing parts with single characters
        $this->transf_patt = "";
        reset( $this->parts );
        while (list(,$p) = each($this->parts))
           $this->transf_patt .= chr(array_search($p, $this->unique_parts)+ord("A"));

        // Self diagnostics
        $this->test_short_search();
    }

    function search( $text )
    {
        // Find all occurences of unique parts in the
        // full text. The result is an array:
        //   Array( <index> => <part#>, .. )
        $part_map = Array();
        reset( $this->unique_parts );
        while (list($pi, $part_str) = each($this->unique_parts))
        {
            $pos = mb_strpos($text, $part_str);
            while ( $pos !== False )
            {
                $part_map[$pos] = $pi;
                $pos = mb_strpos($text, $part_str, $pos+1);
            }
        }
        ksort( $part_map ); // Sort by string index

        // The following code does several things simulatenously:
        //   1) Divide the indices into groups using gaps
        //      larger than $this->max_err as boundaries.
        //   2) Translate the groups into strings so that
        //      part# 0 = 'A', part# 1 = 'B' etc. to make
        //      a low resolution approximate search possible later
        //   3) Save the string indices in the full string
        //      that correspond to characters in the translated string.
        //   4) Discard groups (=part sequences) that are too
        //      short to contain the approximate pattern.
        //
        // The format of resulting array:
        //   Array(
        //      Array( "<translate-string>",
        //             Array( <translated-idx> => <full-index>, ... ) ),
        //      ... )
        $transf = Array();
        $transf_text = "";
        $transf_pos = Array();
        $last_end = 0;
        $group_len = 0;
        reset( $part_map );
        while (list($i,$p) = each($part_map))
        {
            if ( $i-$last_end > $this->max_part_len+$this->max_err )
            {
                if ( $group_len >= ($this->n_parts-$this->max_err))
                    $transf[] = Array( $transf_text, $transf_pos );

                $transf_text = "";
                $transf_pos = Array();
                $group_len = 0;
            }

            $transf_text .= chr($p + ord("A"));
            $transf_pos[] = $i;
            $group_len++;
            $last_end = $i + mb_strlen($this->parts[$p]);
        }
        if ( mb_strlen( $transf_text ) >= ($this->n_parts-$this->max_err))
            $transf[] = Array( $transf_text, $transf_pos );

        unset( $transf_text, $transf_pos );

        if ( current($transf) === False )
            return Array();

        // Filter the remaining groups ("approximate anagrams"
        // of the pattern) and leave only the ones that have enough
        // parts in correct order. You can think this last step of the
        // algorithm as a *low resolution* approximate string search.
        // The result is an array of candidate text spans to be scanned:
        //   Array( Array(<full-start-idx>, <full-end-idx>), ... )
        $part_positions = Array();
        while (list(,list($str, $pos_map)) = each($transf))
        {
//            print "|$transf_patt| - |$str|\n";
            $lores_matches = $this->search_short( $this->transf_patt, $this->max_err, $str );
            while (list($tr_end, ) = each($lores_matches))
            {
                $tr_start = max(0, $tr_end - $this->n_parts);
                if ( $tr_end >= $tr_start )
                {
                    $median_pos = $pos_map[ (int)(($tr_start+$tr_end)/2) ];
                    $start = $median_pos - ($this->patt_len/2+1) - $this->max_err - $this->max_part_len;
                    $end = $median_pos + ($this->patt_len/2+1) + $this->max_err + $this->max_part_len;

//                    print "#" . strtr(substr( $text, $start, $end-$start ), "\n\r", "$$") . "#\n";
//                    print_r( $this->search_short( &$this->patt, $this->max_err, &$text, $start, $end-$start ));

                    $part_positions[] = Array($start, $end);
                }
            }
            unset( $lores_matches );
        }
        unset( $transf );

        if ( current($part_positions) === False )
            return Array();

        // Scan the final candidates and put the matches in a new array:
        $matches = Array();
        $text_len = mb_strlen($text);
        while (list(, list($start, $end)) = each($part_positions))
        {
            $m = $this->search_short( $this->patt, $this->max_err, $text, $start, $end-$start, $text_len );
            while (list($i, $cost) = each($m))
                $matches[$i] = $cost;
        }
        unset($part_positions);

        return $matches;
    }
}

?>