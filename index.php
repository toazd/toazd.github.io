<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="6771589_book_education_idea_learning_school_icon.png">
    <link rel="stylesheet" href="style.css">
    <title>Outline</title>
    <meta name="author" content="wmcdannell at gmail dot com">
    <meta name="description" content="Outline of Biblical usage for words">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div id="search_form">
        <form action="" method="post" name="search_form" enctype="text/plain accept-charset=UTF-8">
            <table>
                <tr>
                    <td colspan=3>
                        <a href="<?php echo $_SERVER["REQUEST_URI"]; ?>" class="largefont">Outline of Biblical usage</a>
                    </td>
                </tr>
                <tr>
                    <td colspan=3>
                        <input type="text" inputmode="search" name="criteria" class="search_criteria" value=""
                            placeholder="type here" maxlength=255 autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="case_sensitivity">case-sensitive
                    </td>
                    <td>
                        <input type="checkbox" name="show_exact_matches" checked>show <span
                            class="exact_match">exact</span> matches
                    </td>
                    <td>
                        <input type="checkbox" name="show_strongs">show strongs
                    </td>
                </tr>
                <tr>
                    <td colspan=3>
                        <input type="submit" name="submit_button" class="submit_button" value="Search">
                    </td>
                </tr>
            </table>
            <?php
            $start_time = microtime(true);

            register_shutdown_function('UnsetVarsOnShutdown');

            # Set debug to false to disable PHP error reporting and other debug features
            $debug = false;
            if ($debug) {
                echo "<p class=\"debug\">DEBUG MODE ENABLED</p>";
            }
            if ($debug) {
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                error_reporting(E_ALL);
                ini_set('max_execution_time', '0');
            }

            # requires php mbstring extension
            # sudo apt install php-mbstring
            mb_internal_encoding("UTF-8");

            # Get option values from the checkboxes
            #
            # i = true, case-insensitive
            # "" = false, case-sensitive
            if (isset($_POST['case_sensitivity'])) {
                $case_sensitive = "";
            } else {
                $case_sensitive = "i";
            }

            if (isset($_POST['show_exact_matches'])) {
                $show_exact_matches = true;
            } else {
                $show_exact_matches = false;
            }

            if (isset($_POST['show_strongs'])) {
                $show_strongs = true;
            } else {
                $show_strongs = false;
            }

            # Bible text used to search.
            # this script expects the following format:
            # book chapter#:verse#|verse{strongs} text {strongs} {strongs}
            $bible_text = "kjvs.csv";

            if (isset($_POST['submit_button'])) {

                # Get the text to search for from the criteria input field
                $search_for = $_POST['criteria'];

                # remove special characters that don't belong (leave :)
                # . \ + * ? [ ^ ] $ ( ) { } = ! < > | - #
                if ($debug === false) {
                    #$search_for = str_replace(["[", "]", ".", "+", "*", "?", "^", "\\", "/", "$", "(", ")", "{", "}", "=", "!", "<", ">", "|", "#", "\r", "\n", "\r\n", "%", "&", "@"], "", $search_for);
                    $search_for = preg_replace("#[^\w:\d ]+#", "", $search_for);
                }

                # if the search criteria is too short, display an error and don't do anything
                if (mb_strlen(trim($search_for)) <= 2 && $debug === false) {
                    ExitWithException("Search criteria \"$search_for\" is too short.");
                }

                # ignore very common words to avoid unncessarily high processing times
                if (preg_match("#^the$|^and$|^that$|^shall$|^unto$|^for$|^his$|^her$|^they$|^him$|^not$|^them$|^with$|^all$|^thou$|^thy$|^was$|^man$|^men$|^which$|^where$|^when$#", trim($search_for))) {
                    ExitWithException("Ignoring common word \"$search_for\"");
                }

                # global vars
                $hits = 0;
                $strongs_array = [];
                $strongs_num = "";
                $h_found = false;
                $g_found = false;
                $exact_matches = 0;
                $related_matches = 0;
                $lines_checked = 0;
                $book_verse_mode = false;

                # if searching for only a strongs number
                if (preg_match("/[GH][\d]{1,4}/", $search_for)) {
                    $search_for_strongs = true;
                } else {
                    $search_for_strongs = false;
                }

                if ($search_for_strongs) {
                    $strongs_array = ["$search_for" => "supplied"];
                }

                $matches = [];
                # detect if a book chapter:verse is supplied to get the strongs num from
                if (preg_match("#^(\d?[ ]?\w+)[ ]?(\d{1,3}):(\d{1,3}) (\w+)(.*)#", $search_for, $matches) && $search_for_strongs === false) {
                    # var_dump($matches);
                    # matches(6) {
                    #    [0]=> whole match
                    #       string(20) "1 john 2:5 perfected"
                    #    [1]=> book
                    #       string(6) "1 john"
                    #    [2]=> chapter
                    #       string(1) "2"
                    #    [3]=> verse
                    #       string(1) "5"
                    #    [4]=> word to find strongs for (others are ignored)
                    #       string(9) "perfected"
                    #    [5]=> extra words that are ignored
                    #  }
                    $book_verse_mode = true;
                    $bookname = TransformBookNames($matches[1]);
                    $chapter = $matches[2];
                    $verse_number = $matches[3];
                    $word_to_find_strongs_for = $matches[4];
                    $book_chapter_verse = "$bookname $chapter:$verse_number";

                    # show a warning if extra words were supplied beyond the first
                    if (array_key_exists(5, $matches) && $matches[5] != "") {
                        echo "<p class=\"center debug\">Ignoring extra words:<BR>\"$matches[5]\"</p>";
                    }

                    $found = false;
                    # open the file of the text to search in read only mode
                    $handle = @fopen($bible_text, "r");

                    if ($handle) {

                        # get one line at a time from the file
                        while (($line = fgets($handle)) !== false) {
                            $line = str_replace(["\r", "\n", "\r\n"], "", $line);
                            $lines_checked += 1;
                            if (stripos($line, $book_chapter_verse . "|", 0) !== false && $found !== true) {
                                $found = true;
                                break;
                            }
                        }

                        if ($found !== true) {
                            ExitWithException("Could not find the [book chapter:verse] \"" . $book_chapter_verse . "\" provided.<BR>Please check the spelling of the book name and make sure that the chapter:verse exists.</p>");
                        } elseif ($found) {
                            $strongs_num = ExtractStrongsNumber($line, $word_to_find_strongs_for, $case_sensitive);
                            $book_verse_mode = true;
                            if ($strongs_num != "") {
                                $strongs_array += ["$strongs_num" => "$book_chapter_verse"];
                                $search_for = $word_to_find_strongs_for;
                            }
                        }
                    }
                } elseif (preg_match("#^(\d?[ ]?\w+)[ ]?(\d{1,3}):(\d{1,3})$#", $search_for)) {
                    ExitWithException("When providing a book chapter:verse, you must also supply a word to find the Strong's number for.");
                }

                if ($search_for_strongs && $book_verse_mode === false) {
                    echo "<p class=\"center bold underline\">Search results for \"$search_for\"</p>";
                } elseif ($search_for_strongs === false && $show_exact_matches && $book_verse_mode === false) {
                    echo "<p class=\"center\"><span class=\"bold underline\">Search results for \"$search_for\"</span><BR><span class=\"normal\">(exact matches included)</span></p>";
                } elseif ($book_verse_mode) {
                    echo "<p class=\"center\"><span class=\"bold underline\">Search results for \"$book_chapter_verse $word_to_find_strongs_for\"</span><BR><span class=\"normal\">(exact matches included)</span></p>";
                } elseif ($show_exact_matches === false) {
                    echo "<p class=\"center\"><span class=\"bold underline\">Search results for \"$search_for\"</span><BR><span class=\"normal\">(exact matches excluded)</span></p>";
                }

                # get strongs nums for the word/phrase searched for
                if ($book_verse_mode === false && $search_for_strongs === false) {
                    # open the file of the text to search in read only mode
                    $handle = @fopen($bible_text, "r");

                    if ($handle) {

                        $lines_checked = 0;
                        # get one line at a time from the file
                        while (($line = fgets($handle)) !== false) {
                            $line = str_replace(["\r", "\n", "\r\n"], "", $line);

                            $lines_checked += 1;
                            $match_array = [];
                            $dataline_arr = explode("|", $line);
                            $book_chapter_verse = $dataline_arr[0];
                            $verse_text = $dataline_arr[1];

                            # match " search_for{"
                            if (preg_match_all("#\b$search_for\{[GH]\d{1,4}\}#$case_sensitive", $verse_text, $match_array) !== false) {

                                $hits = count($match_array[0]);

                                # if exactly one hit per line add it
                                if ($hits == 1) {
                                    $strongs_num = ExtractStrongsNumber($line, $search_for, $case_sensitive);

                                    if ($strongs_num != "") {
                                        $strongs_array += ["$strongs_num" => "$book_chapter_verse"];
                                    }
                                } elseif ($hits > 1) {
                                    # if more than one hit per line, add the first one, remove it, add the next, and so on
                                    $multi_line = explode("|", $line);
                                    $multi_line = $multi_line[1];
                                    while (preg_match("#\b$search_for\{#$case_sensitive", $multi_line)) {

                                        $strongs_num = ExtractStrongsNumber($multi_line, $search_for, $case_sensitive);

                                        if ($strongs_num != "") {
                                            $strongs_array += ["$strongs_num" => "$book_chapter_verse"];
                                        }

                                        # remove portion of the line that a strongs num was already added for
                                        $multi_line = substr($multi_line, strpos($line, $strongs_num) + mb_strlen($strongs_num) + 1);
                                    }
                                }
                            }
                        }
                    }
                    # reset the file position pointer to check it from beginning to end again
                    fclose($handle);
                }

                # remove duplicates
                $strongs_array = array_unique($strongs_array, SORT_REGULAR);

                # don't print the report if a strongs number was searched for
                if ($search_for_strongs === false && count($strongs_array) > 0) {
                    # print a report of the strongs nums
                    if ($case_sensitive == "") {
                        echo "<center><span class=\"underline\">Strongs numbers associated with \"$search_for\"</span><BR>(case-sensitive)</center><BR>";
                    } elseif ($case_sensitive == "i") {
                        echo "<center><span class=\"underline\">Strongs numbers associated with \"$search_for\"</span></center><BR>";
                    }
                    echo "<TABLE>";
                    if (count($strongs_array) > 0) {
                        foreach ((array) $strongs_array as $strongs_num => $book_verse_source) {
                            echo "<TR><TD class=\"strongsdata alignright\"><span class=\"book\">$book_verse_source</span></TD><TD class=\"strongsdata alignleft\">$strongs_num</TD></TR>";
                        }
                    }
                    echo "</TABLE><hr>";
                }

                # if any strongs nums were found
                if (count($strongs_array) > 0) {

                    # open the file of the text to search in read only mode
                    $handle = @fopen($bible_text, "r");

                    if ($handle) {

                        $lines_checked = 0;

                        # get one line at a time from the file
                        while (($line = fgets($handle)) !== false) {
                            $line = str_replace(["\r", "\n", "\r\n"], "", $line);
                            $lines_checked += 1;

                            $dataline_arr = explode("|", $line);
                            $book_chapter_verse = $dataline_arr[0];
                            $verse_text = $dataline_arr[1];

                            if ($search_for_strongs) {
                                if (strpos($verse_text, "{{$search_for}}") !== false) {
                                    $exact_matches += 1;
                                    PrintMatchedVerse($line, $strongs_array, false, $case_sensitive);
                                }
                            } elseif ($search_for_strongs === false) {
                                # loop through the strongs nums found and find verses that match
                                foreach ((array) $strongs_array as $strongs_num => $book_verse_source) {

                                    # find exact matches
                                    if (preg_match("#\b$search_for\{$strongs_num\}#$case_sensitive", $verse_text)) {
                                        $exact_matches += 1;
                                        if ($show_exact_matches) {
                                            PrintMatchedVerse($line, $strongs_array, true, $case_sensitive);
                                            # after a match is found, go to the next line
                                            break;
                                        } elseif ($show_exact_matches === false) {
                                            continue;
                                        }

                                        # related matches
                                    } else {
                                        if (preg_match("#\{$strongs_num\}#$case_sensitive", $verse_text)) {
                                            $related_matches += 1;
                                            PrintMatchedVerse($line, $strongs_array, false, $case_sensitive);
                                            # after a match is found, go to the next line
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    fclose($handle);
                }

                echo "<hr>";

                if ($show_exact_matches === true) {
                    echo "<p class=\"center\">$exact_matches exact matches</p>";
                } elseif ($show_exact_matches === false) {
                    echo "<p class=\"center\">$exact_matches exact matches excluded</p>";
                }

                if ($search_for_strongs === false) {
                    echo "<p class=\"center\">$related_matches related matches found</p>";
                }

                $end_time = microtime(true);
                echo "<p class=\"center\"> Finished in " . round($end_time - $start_time, 3) . " seconds</p>";
            }

            # Format the data line for printing
            function PrintMatchedVerse(string $line, array $strongs_array, bool $exact_match, string $case_sensitive): void
            {
                global $show_strongs;

                $line_arr = explode("|", $line);
                $line_arr[1] = HighlightBeforeStrongsNums($line_arr[1], $strongs_array, $case_sensitive);

                if ($show_strongs) {
                    $line_arr[1] = StripStrongsNumsButExclude($line_arr[1], $strongs_array);
                    $line_arr[1] = preg_replace("#\{([GH]\d{1,4})\}#", "<sup class=\"strongs\">" . "$1" . "</sup>", $line_arr[1]);
                } elseif ($show_strongs === false) {
                    $line_arr[1] = StripStrongsNums($line_arr[1]);
                }

                if ($exact_match) {
                    echo "<p>" . "<span class=\"book\">" . $line_arr[0] . "</span> <span class=\"exact_match\">" . $line_arr[1] . "</span></p>";
                } elseif ($exact_match === false) {
                    echo "<p>" . "<span class=\"book\">" . $line_arr[0] . "</span> " . $line_arr[1] . "</p>";
                }
            }

            # Strip all but the specified strongs nums from the line
            function StripStrongsNumsButExclude(string $str, array $exclude): string
            {
                $offset = 0;
                $offset_end = mb_strlen($str);
                $skip = false;

                # from left to right on str, check each strongs number if it is to be kept or removed
                while (($offset < $offset_end) && strpos($str, "{", $offset) !== false) {
                    $start = strpos($str, "{", $offset);
                    $end = strpos($str, "}", $start);
                    foreach ($exclude as $strongs_num => $bookversesource) {
                        $substr = substr($str, $start + 1, ($end - $start) - 1);
                        if (strpos("{" . $substr . "}", "{" . $strongs_num . "}") !== false) {
                            $skip = true;
                            break;
                        } else {
                            $skip = false;
                        }
                    }

                    if ($skip) {
                        $offset = $end;
                    } elseif ($skip === false) {
                        # if there's a space before the strongs # to be removed, remove it as well
                        # changes " {remove}" => "{remove}"
                        if (substr($str, $start - 1, 1) == " ") {
                            $start = $start - 1;
                        }

                        # remove the found strongs number that is not in the exclude array
                        $str = substr_replace($str, "", $start, ($end - $start) + 1);
                    }

                    $offset_end = mb_strlen($str);
                }

                # fix "word {strongs}" to "word{strongs}
                # strip_tags is necessary because of the highlighting tags
                # xdebug optimization
                if (strpos(strip_tags($str), " {") !== false) {
                    #if (preg_match("#\b\w+ \{[GH]\d{1,4}\}#i", strip_tags($str))) {
                    $str = str_replace(" {", "{", $str);
                }
                #}
            
                return $str;
            }

            # Strip all the strongs nums from a data line
            function StripStrongsNums(string $str): string
            {
                $str = preg_replace("#\{[GH]\d{1,4}\} \{[GH]\d{1,4}\} \{[GH]\d{1,4}\}|\{[GH]\d{1,4}\} {[GH]\d{1,4}\}|\{[GH]\d{1,4}\}#", "", $str);
                return $str;
            }

            # Split a data line and return only the verse text (removes book chapter:verse|)
            function StripDataLineToVerseText(string $str): string
            {
                $str_arr = explode("|", $str);
                $str = $str_arr[1];

                return $str;
            }

            # highlight the text preceding a matched strongs num
            function HighlightBeforeStrongsNums($str, array $arr, $case_sensitive): string
            {
                global $debug, $show_strongs;
                $matches = [];
                $hl_beg = "<span class=\"highlight\">";
                $hl_end = "</span>";

                foreach ($arr as $strongs_num => $bookversesource) {
                    # match "text{not_a_match} {match}" OR "text{match}" in that order
                    if (preg_match_all("#\w+[ ]?\w+\{[GH]\d{1,4}\} \{$strongs_num\}|\w+[ ]?\w+\{$strongs_num\}#", $str, $matches, PREG_OFFSET_CAPTURE) > 0) {
                        # iterate the array of arrays returned in matches
                        foreach ($matches[0] as $match) {
                            foreach ($match as $term) {
                                #TERM: would{G4717}
                                #TERM: 142   
                                #TERM: have crucified{G4717}
                                #TERM: 166
                                # only check terms that contain a strongs num
                                if (preg_match("#\{[GH]\d{1,4}\}#", $term)) {
                                    # strip term down to just text (no strongs nums)
                                    # xdebug optimization (substr vs preg_replace)
                                    $term = substr($term, 0, strpos($term, "{"));
                                    #echo "TERM: \"$term\"<BR>";
                                    #echo "STRONGSNUM: \"$strongs_num\"<BR>";
                                    #$term = StripStrongsNums($term);
                                    #echo "str: \"$str\"<BR>";
            
                                    # if the option to show strongs is true, don't add the tooltips (which show strongs nums)
                                    if ($show_strongs) {
                                        $str = preg_replace("#$term\{$strongs_num}|$term\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}|$term\{[GH]\d{1,4}\}[\pP ]+?\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}|$term\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}[\pP ]+?\{[GH]\d{1,4}\}[\pP ]+?#", $hl_beg . $term . $hl_end . "{{$strongs_num}}", $str);
                                    } elseif ($show_strongs === false) {
                                        # if strongs are hidden then add them to hover tooltips
                                        # match "$term{match}" OR "$term{notamatch}_{match}" OR "$term{notamatch}_{match}_{notamatch}"
                                        # OR "$term{notamatch}_{notamatch}_{match}" where _ can be any punctuation or a space
                                        $str = preg_replace("#$term\{$strongs_num}|$term\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}|$term\{[GH]\d{1,4}\}[\pP ]+?\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}|$term\{[GH]\d{1,4}\}[\pP ]+?\{$strongs_num\}[\pP ]+?\{[GH]\d{1,4}\}[\pP ]+?#", $hl_beg . "<span class=\"tooltip\"><span class=\"tooltiptext\">$strongs_num</span>" . $term . "</span>" . $hl_end . "{{$strongs_num}}", $str);
                                    }
                                }
                            }
                        }
                    }
                }

                return $str;
            }

            # high strongs nums
            function HighlightStrongsNums($str, array $arr): string
            {
                foreach ($arr as $strongs_num => $bookversesource) {
                    if ($strongs_num != "") {
                        #$count = substr_count($str, $strongs_num);
                        if (strpos($str, $strongs_num) !== false) {
                            $str = str_replace($strongs_num, "<span class=\"highlight\">" . $strongs_num . "</span>", $str);
                            #$str = HighlightNoTooltip($str, $strongs_num);
                        }
                    }
                }
                return $str;
            }

            # check if a verse line contains any of the strongs nums given
            function CheckIfLineContains($str, array $arr): bool
            {
                foreach ($arr as $a) {
                    if (strpos($str, "{{$a}}") !== false) {
                        return true;
                    }
                }
                return false;
            }

            # Return the strongs num for a supplied word, or the closest one to the right
            # if the word supplied is part of a phrase
            function ExtractStrongsNumber($line, $search_for, $case_sensitive): string
            {
                global $debug;

                if (strpos($line, "|") !== false) {
                    $line_arr = explode("|", $line);
                    $line = $line_arr[1];
                }

                $matches = [];

                #if (preg_match_all("#\b{$search_for}[ \w]*\{#$case_sensitive", $line, $matches, PREG_OFFSET_CAPTURE) > 0) {
                if (preg_match_all("#\b$search_for\{[GH]\d{1,4}\}#$case_sensitive", $line, $matches, PREG_OFFSET_CAPTURE) > 0) {
                    #$skipterm = $matches[0][0][1];
                    #echo "<pre>";
                    #var_dump($matches);
                    #echo "</pre>";
            
                    foreach ($matches as $match) {
                        foreach ($match as $key) {
                            foreach ($key as $value) {
                                $skipterm = $value;
                            }
                        }
                    }

                    $checkstring = substr($line, $skipterm);

                    # detect striple strongs
                    if (preg_match("#\b$search_for\{[GH]\d{1,4}\}[\pP ]?\{[GH]\d{1,4}\}[\pP ]?\{[GH]\d{1,4}\}#i", $checkstring)) {
                        $startpos = strpos($line, "{", $skipterm);
                        $endpos = strpos($line, "}", $startpos);
                        # move the end to the second of the triplet
                        $endpos = strpos($line, "{", $endpos);
                        $endpos = strpos($line, "}", $endpos);
                        # move the end to the third of the triplet
                        $endpos = strpos($line, "{", $endpos);
                        $endpos = strpos($line, "}", $endpos);

                        $strongs = substr($line, $startpos + 1, ($endpos - $startpos) - 1);
                        $strongs = str_replace("{", "", $strongs);
                        $strongs = str_replace("}", "", $strongs);

                        # detect double strongs pairs
                    } else if (preg_match("#\b$search_for\{[GH]\d{1,4}\}[\pP ]?\{[GH]\d{1,4}\}#i", $checkstring)) {
                        $startpos = strpos($line, "{", $skipterm);
                        $endpos = strpos($line, "}", $startpos);
                        $first_strongs = substr($line, $startpos + 1, $endpos - $startpos - 1);
                        # move the end to the second of the pair
                        $endpos = strpos($line, "{", $endpos);
                        $second_strongs_startpos = strpos($line, "{", $endpos);
                        $endpos = strpos($line, "}", $endpos);

                        $second_strongs = substr($line, $second_strongs_startpos + 1, $endpos - $second_strongs_startpos - 1);

                        # if the first and second strongs pairs are the same, return only one
                        # eg. Job 42:10 captivity
                        if ($first_strongs == $second_strongs) {
                            return $first_strongs;
                        }

                        $strongs = substr($line, $startpos + 1, ($endpos - $startpos) - 1);
                        $strongs = str_replace("{", "", $strongs);
                        $strongs = str_replace("}", "", $strongs);


                    } else {
                        $startpos = strpos($line, "{", $skipterm);
                        $endpos = strpos($line, "}", $startpos);
                        $strongs = substr($line, $startpos + 1, ($endpos - $startpos) - 1);
                    }

                    # if the pair is not separated by a space, put a space there
                    $strongs = preg_replace("#([GH]\d{1,4})[^ \n]?([GH]\d{1,4})#", "$1 $2", $strongs);

                    return $strongs;
                } else {
                    # grab the nearest strongs num to the right of search_for
                    # if search_for is within a phrase and not next to it
                    $trimmed = substr($line, strpos($line, $search_for) + mb_strlen($search_for));
                    $startpos = strpos($trimmed, "{");
                    $endpos = strpos($trimmed, "}", $startpos);
                    $strongs = substr($trimmed, $startpos + 1, ($endpos - $startpos) - 1);

                    # if the pair is not separated by a space, put a space there
                    #$strongs = preg_replace("#([GH]\d{1,4})[^ \n]?([GH]\d{1,4})#", "$1 $2", $strongs);
                    return $strongs;
                }
            }

            # exit with a supplied error and provide the line that called it from the script
            function ExitWithException($message): never
            {
                if (mb_strlen(trim($message)) > 3) {
                    echo "<p class=\"debug\">$message</p>";
                    print_calling_line();
                    exit(1);
                } else {
                    echo "<p class=\"debug\">PROCESS ABORTED</p>";
                    print_calling_line();
                    exit(1);
                }
            }

            # highlight the supplied term in the line provided
            function HighlightNoTooltip($line, $term, $case_sensitive): string
            {
                $hl_beg = "<span class=\"highlight\">";
                $hl_end = "</span>";

                return preg_replace("/\b($term)\b/$case_sensitive", $hl_beg . "$1" . $hl_end, $line);
            }

            # print the line of the script that called
            function print_calling_line(): void
            {
                $backtrace = debug_backtrace();
                print "<p class=\"debug\">Called from line " . $backtrace[1]['line'] . "</p>";
            }

            # transform booknames to match those of the data file
            function TransformBookNames($string_to_check): string
            {
                $string_to_check = trim($string_to_check);
                $abbrev_booknames = array(
                    "genesis" => "Gen",
                    "exodus" => "Exo",
                    "exod" => "Exo",
                    "ex" => "Exo",
                    "leviticus" => "Lev",
                    "levit" => "Lev",
                    "levi" => "Lev",
                    "Numbers" => "num",
                    "Numb" => "num",
                    "deuteronomy" => "Deu",
                    "deuter" => "Deu",
                    "deut" => "Deu",
                    "joshua" => "Jos",
                    "josh" => "Jos",
                    "judges" => "Jdg",
                    "judg" => "Jdg",
                    "jgs" => "Jdg",
                    "ruth" => "Rth",
                    "rut" => "Rth",
                    "rt" => "Rth",
                    "1 samuel" => "1Sa",
                    "2 samuel" => "2Sa",
                    "1sam" => "1Sa",
                    "2sam" => "2Sa",
                    "1 kings" => "1Ki",
                    "2 kings" => "2Ki",
                    "1kings" => "1Ki",
                    "2kings" => "2Ki",
                    "1 kgs" => "1Ki",
                    "2 kgs" => "2Ki",
                    "1kgs" => "1Ki",
                    "2kgs" => "2Ki",
                    "1kg" => "1Ki",
                    "2kg" => "2Ki",
                    "1 chronicles" => "1Ch",
                    "2 chronicles" => "2Ch",
                    "1chronicles" => "1Ch",
                    "2chronicles" => "2Ch",
                    "1 chron" => "1Ch",
                    "2 chron" => "2Ch",
                    "1chron" => "1Ch",
                    "2chron" => "2Ch",
                    "1 chr" => "1Ch",
                    "2 chr" => "2Ch",
                    "1chr" => "1Ch",
                    "2chr" => "2Ch",
                    "ezra" => "Ezr",
                    "ez" => "Ezr",
                    "nehemiah" => "Neh",
                    "nehe" => "Neh",
                    "ne" => "Neh",
                    "esther" => "Est",
                    "esth" => "Est",
                    "es" => "Est",
                    "psalms" => "Psa",
                    "psalm" => "Psa",
                    "pss" => "Psa",
                    "ps" => "Psa",
                    "proverbs" => "Pro",
                    "proverb" => "Pro",
                    "prov" => "Pro",
                    "qoheleth" => "Ecc",
                    "Ecclesiastes" => "Ecc",
                    "Eccles" => "Ecc",
                    "qoh" => "Ecc",
                    "song of solomon" => "Son",
                    "canticles" => "Son",
                    "canticle" => "Son",
                    "song" => "Son",
                    "isaiah" => "Isa",
                    "esaias" => "Isa",
                    "jeremiah" => "Jer",
                    "jere" => "Jer",
                    "lamentations" => "Lam",
                    "lamen" => "Lam",
                    "la" => "Lam",
                    "ezekiel" => "Eze",
                    "ezek" => "Eze",
                    "daniel" => "Dan",
                    "da" => "Dan",
                    "hosea" => "Hos",
                    "joel" => "Joe",
                    "amos" => "Amo",
                    "am" => "Amo",
                    "obadiah" => "Oba",
                    "obad" => "Oba",
                    "jonah" => "Jon",
                    "jona" => "Jon",
                    "micah" => "Mic",
                    "mi" => "Mic",
                    "nahum" => "Nah",
                    "na" => "Nah",
                    "Habbakkuk" => "Hab",
                    "habak" => "Hab",
                    "habb" => "Hab",
                    "haba" => "Hab",
                    "hab" => "Hab",
                    "ha" => "Hab",
                    "Zephaniah" => "Zep",
                    "Zeph" => "Zep",
                    "ze" => "Zep",
                    "Haggai" => "Hag",
                    "Hagg" => "Hag",
                    "Zechariah" => "Zec",
                    "Zech" => "Zec",
                    "zec" => "Zec",
                    "Malachi" => "Mal",
                    "Mala" => "Mal",
                    "Matthew" => "Mat",
                    "matt" => "Mat",
                    "mt" => "Mat",
                    "mark" => "Mar",
                    "mk" => "Mar",
                    "luke" => "Luk",
                    "lu" => "Luk",
                    "lk" => "Luk",
                    "john" => "Joh",
                    "jhn" => "Joh",
                    "jno" => "Joh",
                    "jn" => "Joh",
                    "acts" => "Act",
                    "ac" => "Act",
                    "romans" => "Rom",
                    "roman" => "Rom",
                    "ro" => "Rom",
                    "1Corinthians" => "1Co",
                    "2corinthians" => "2Co",
                    "1 corinthians" => "1Co",
                    "2 corinthians" => "2Co",
                    "1 cor" => "1Co",
                    "2 cor" => "2Co",
                    "1cor" => "1Co",
                    "2cor" => "2Co",
                    "Galations" => "Gal",
                    "Gala" => "Gal",
                    "ga" => "Gal",
                    "ephesians" => "Eph",
                    "ep" => "Eph",
                    "Philippians" => "Phi",
                    "Colossians" => "Col",
                    "Colo" => "Col",
                    "Co" => "Col",
                    "1 Thessalonians" => "1Th",
                    "2 thessalonians" => "2Th",
                    "1Thessalonians" => "1Th",
                    "2thessalonians" => "2Th",
                    "1 Thess" => "1Th",
                    "2 thess" => "2Th",
                    "1Thess" => "1Th",
                    "2thess" => "2Th",
                    "1 thes" => "1Th",
                    "2 thes" => "2Th",
                    "1thes" => "1Th",
                    "2thes" => "2Th",
                    "1 ths" => "1Th",
                    "2 ths" => "2Th",
                    "1 th" => "1Th",
                    "2 th" => "2Th",
                    "1 timothy" => "1Ti",
                    "2 timothy" => "2Ti",
                    "1timothy" => "1Ti",
                    "2timothy" => "2Ti",
                    "1 tim" => "1Ti",
                    "2 tim" => "2Ti",
                    "1tim" => "1Ti",
                    "2tim" => "2Ti",
                    "1 ti" => "1Ti",
                    "2 ti" => "2Ti",
                    "titus" => "Tit",
                    "ti" => "Tit",
                    "Philemon" => "Phm",
                    "Phil" => "Phm",
                    "hebrews" => "Heb",
                    "hebrew" => "Heb",
                    "hebr" => "Heb",
                    "he" => "Heb",
                    "james" => "Jam",
                    "jms" => "Jam",
                    "jas" => "Jam",
                    "1 peter" => "1Pe",
                    "2 peter" => "2Pe",
                    "1peter" => "1Pe",
                    "2peter" => "2Pe",
                    "1 Pet" => "1Pe",
                    "2 Pet" => "2Pe",
                    "1Pet" => "1Pe",
                    "2Pet" => "2Pe",
                    "1 john" => "1Jo",
                    "2 john" => "2Jo",
                    "3 john" => "3Jo",
                    "1john" => "1Jo",
                    "2john" => "2Jo",
                    "3John" => "3Jo",
                    "1 joh" => "1Jo",
                    "2 joh" => "2Jo",
                    "3 joh" => "3Jo",
                    "1joh" => "1Jo",
                    "2joh" => "2Jo",
                    "3joh" => "3Jo",
                    "1jno" => "1Jo",
                    "2jno" => "2Jo",
                    "3jno" => "3Jo",
                    "1jn" => "1Jo",
                    "2jn" => "2Jo",
                    "3jn" => "3Jo",
                    "jude" => "Jud",
                    "jd" => "Jud",
                    "Revelation" => "Rev",
                    "Revel" => "Rev",
                    "re" => "Rev",
                );

                # Check if the search string begins with any of the supported abbreviated book names and replace it if found
                foreach ($abbrev_booknames as $key => $value) {
                    if (substr_compare($string_to_check, $key, 0, strlen($key), true) == 0) {
                        $string_to_check = preg_replace("/\b$key\b/i", $value, $string_to_check, 1);
                        break;
                    }
                }

                # uniform capitalization
                $string_to_check = strtolower($string_to_check);
                $string_to_check = ucfirst($string_to_check);

                return $string_to_check;
            }

            # unset all set variables on script exit. requires registration.
            function UnsetVarsOnShutdown(): void
            {
                foreach (array_keys(get_defined_vars()) as $defined_var) {
                    unset(${$defined_var});
                }
                unset($defined_var);
            }
            ?>
        </form>
    </div>
    <a href="#" class="scrollbutton" id="scrollbuttonid">↑</a>
    <script src="script.js"></script>
</body>

</html>