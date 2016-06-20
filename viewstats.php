<?php
 
// The Collaborative Learning Environment (CLE) is an assignment plugin for Moodle that allows groups to work in real-time on assignments and provides insight to teachers into individual student contributions and group collaboration via usage statistics.

// Copyright (C) 2016 EBTIC

//  CLE is free software designed to work with the Moodle Learning Management system: 
//  you can redistribute it and/or modify it under the terms of the GNU General Public 
//  License as published by the Free Software Foundation, either version 3 of the 
//  License, or (at your option) any later version.

//  CLE is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.

//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

/// For more information on Moodle see - http://moodle.org/

/**
 * This file enables the CLE plugin to be used within the Moodle learning management system 
 *
 * @since      Moodle 2.8.1
 * @package    cle
 * @copyright  2016 EBTIC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('stats.php');
 
require_once('../../../../config.php');
$cmid = required_param('id', PARAM_INT);
$pad = optional_param('pad', '', PARAM_TEXT);
$groupID = optional_param('groupID', 0, PARAM_INT);
$course = $DB->get_record('course', array('id' => $cmid), '*', MUST_EXIST);
$authorstore = array();
$authornames = NULL;

global $PAGE, $OUTPUT,$COURSE;
require_login($course, true);
// check if user is allowed to ciew pad
$context = context_course::instance($course->id);
require_capability('mod/assign:grade', $context);        
        
$PAGE->set_url('/mod/assign/submission/cle/viewstats.php', array('id' => $cmid, 'pad' => $pad));
$PAGE->set_title('Statistics');
$PAGE->set_heading('Group Statistics');
$PAGE->set_pagelayout('popup');
$PAGE->set_cacheable(false);

$stats = get_config('assignsubmission_cle', 'etherpad_stats');
// If stats are disabled in the settings, do not do anything
if ($stats==0) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading("Statistics are disabled. ");
    echo $OUTPUT->box("Please contact your administrator to enable statistics");
    echo $OUTPUT->close_window_button();
    echo $OUTPUT->footer();
    return;
}
// else initialise necessary variables and connect to the etherpad database
$group = groups_get_group_name($groupID);
$username = get_config('assignsubmission_cle', 'etherpad_user');
$password = get_config('assignsubmission_cle', 'etherpad_password');
$database = get_config('assignsubmission_cle', 'etherpad_db');
$host = get_config('assignsubmission_cle', 'etherpad_server');

$link = mysql_connect($host, $username, $password) or die("Cannot connect to Server");
mysql_select_db($database, $link) or die("Unable to select database");

// get all the group member names
$group_members = groups_get_members($groupID, "u.firstname,u.lastname");

getAuthorData_FromAuthorTable();

echo $OUTPUT->header();

echo $OUTPUT->box_start();
$where = "";
if ($pad) {
    $where = "where idpad='$pad'";
}
$query = "SELECT * FROM pad " . $where;

$result = mysql_query($query,$link);
while ($row = mysql_fetch_assoc($result)) {
    //collect pad data
    $query = "SELECT timestamp from changeset where idpad = '" . $row['idpad'] . "' and revision = 0";
    $result2 = mysql_query($query,$link) or die(mysql_error());
    $row2 = mysql_fetch_assoc($result2);
    unset($authornames);
    echo $OUTPUT->heading("Statistics for Group $group",1);
    
    $table = new html_table();
    $table->head = array('Time created', 'Last Change', 'Revisions','Chatlines','Length');
    $table_data[] = $row2['timestamp'];
    $query = "SELECT timestamp from changeset where idpad = '" . $row['idpad'] . "' and revision = " . $row['revisions'];
    $result2 = mysql_query($query,$link) or die(mysql_error());
    $row2 = mysql_fetch_assoc($result2);
    $table_data[] = $row2['timestamp'] ;
    $table_data[] =  $row['revisions'] ;
    $table_data[] =  $row['chatlines'] ;
    $table_data[] =  $row['length'] ;
    $table->data = array($table_data);
    echo html_writer::table($table);
    
    echo $OUTPUT->box_end();
	
    //collect author data
    $query = "SELECT * FROM author,padauthors where padauthors.idpad='" . $row['idpad'] . "' AND author.idauthor = padauthors.idauthor";
    $result2 = mysql_query($query,$link) or die(mysql_error());
    $i = 0;
    while ($row2 = mysql_fetch_assoc($result2)) {
        $authornames[$row2['idauthor']]->name = $row2['name'];
        $authornames[$row2['idauthor']]->color = $row2['color'];
        authorDetails($row['idpad'], $row2['idauthor']);
    }
    
    // move pointer back to beginning of cursor   
    mysql_data_seek($result2, 0);
    $tableauthors = array();
    while ($row2 = mysql_fetch_assoc($result2)) {
        $tableauthors[] = $row2['idauthor'];
    }   
    
    echo $OUTPUT->heading("Author details",3);
    echo"<table>";
    echo "<col>";
    foreach ($tableauthors as $authorid) {
        echo"<col style ='background-color:" . $authorstore[$authorid]->color . ";'>";         
    }

    // if current user is teacher AND is an author, write an extra column
    $current_user = $USER->firstname;                  
    $includes_teacher = false;
    foreach ($tableauthors as $authorid) {        
      $authorname = $authorstore[$authorid]->name;
      if($authorname == $current_user) {
        $tbl_color .= "<col style ='background-color:#BABABA'>";
        $tbl_edits .= "<TD>0</TD>";                                         
        $includes_teacher = true;
        break;
       }                  
     }
        
    // add a static column oolor for non-contributing authors
    if($includes_teacher) {
      $num_non_contrib = count($group_members) - count($tableauthors) + 1;             
      }
    else {
      $num_non_contrib = count($group_members) - count($tableauthors);           
    }    
    
    if ($num_non_contrib > 0) {
        $cntr = 0;        
        $tbl_color = $tbl_edits = "";
        while ($cntr < $num_non_contrib) {
          // build all the table related strings for non-contributing authors - except names
          $tbl_color .= "<col style ='background-color:#BABABA'>";
          $tbl_edits .= "<TD>0</TD>";           
          $cntr++;        
        }
        echo $tbl_color;
    }    
       
    echo"<TR><TH>Name</TH>";
    //  output etherpad authors + non-contributing group members 
    $etherpad_authors = "";
    foreach ($tableauthors as $authorid) {        
        echo"<TD>" . $authorstore[$authorid]->name . "</TD>";
        $etherpad_authors = $etherpad_authors . ", " . $authorstore[$authorid]->name ;        
    }
    
    // if name exists in existing etherpad authors, do not print.  
    foreach ($group_members as $noncontrib_member) {
      $membername = trim($noncontrib_member->firstname) ;   
      $tf_result = strpos($etherpad_authors, $membername);
      if ($tf_result === false) {
         echo"<TD>" . $membername . "</TD>";
      }            
    }    
    
    echo "</TR>";
    echo"<TR><TH>Final Contribution</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . ($authorstore[$authorid]->pad->$row['idpad']->charsadded - $authorstore[$authorid]->pad->$row['idpad']->charsremoved) . "</TD>";
    }
    
    // add details for any non-contributing authors
    if ($num_non_contrib > 0) {
        echo $tbl_edits;
    }    
    
    echo "</TR>";
    echo"<TR><TH>Chars added/deleted</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . $authorstore[$authorid]->pad->$row['idpad']->charsadded . " / " . $authorstore[$authorid]->pad->$row['idpad']->charsremoved . "</TD>";
    }
    
	// add details for any non-contributing authors
    if ($num_non_contrib > 0) {
        echo $tbl_edits;
    }            
    echo "</TR>";
    echo"<TR><TH>Inserts/Deletes/Moves</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . $authorstore[$authorid]->pad->$row['idpad']->pastes . " / " . $authorstore[$authorid]->pad->$row['idpad']->cuts . " / " . $authorstore[$authorid]->pad->$row['idpad']->move;
        echo"</TD>";
    }
    
    // add details for any non-contributing authors
    if ($num_non_contrib > 0) {
        echo $tbl_edits;
    }            
    echo "</TR>";
    echo"</table>";
    echo $OUTPUT->heading("Participation Graph");
    plotPadChar($row['idpad'], $authornames);
}
mysql_close($link);

echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();

// this function will build the authorstore
// This is meant to bypass writing to the author table, since this will already be done.
// These changes have been created to allow for instructors in the CLE trial to use the statistics piece without
// re-building all the tables each time.
function getAuthorData_FromAuthorTable() {
    global $authorstore,$link;
    
    $query = "SELECT * FROM author";
    $authors = mysql_query($query,$link) or die("Cannot run query $query .");        
    $num = mysql_numrows($authors);
    $i = 0;
    while ($i < $num) {    
      $author = mysql_result($authors, $i, "idauthor");        
      $authorstore[$author]->name = mysql_result($authors, $i, "name");
      $authorstore[$author]->color = mysql_result($authors, $i, "color");
      $i++;
    }       
    }

/**
 * collect data to present about each author.
 * @param type $pad
 * @param type $author 
 */
function authorDetails($pad, $author) {
    global $authorstore,$link;
    $authordata = array();
    $counts->adds = 0;
    $counts->additions = array();
    $counts->totaladds = 0;
    $counts->totaldels = 0;
    $counts->removes = array();
    $counts->totalformats = 0;
    $counts->dels = 0;
    $counts->cutandpaste = 0;
    $i = 0;
    $query = "SELECT * from changeset where changeset.idauthor = '" . $author . "' and changeset.idpad = '" . $pad . "'";
    $result = mysql_query($query,$link) or die(mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        if ($row['change'] > 0) {
            $counts->totaladds+=$row['change'];
        } elseif ($row['change'] < 0) {
            $counts->totaldels+=-$row['change'];
        } else {
            $counts->totalformats++;
        }
        // select all copy or paste actions in the changeset 
        if (abs($row['change']) > 10) {
            // extract and store relevant data;
            $authordata[$i]->change = $row['change'];
            $authordata[$i]->revision = $row['revision'];
            $authordata[$i]->type = $row['type'];
            $authordata[$i]->timestamp = $row['timestamp'];
            // count large deletions
            if ($authordata[$i]->type == 0) {
                $counts->dels++;
                $tmp->rev = $row['revision'];
                $tmp->size = abs($row['change']);
                $counts->removes[] = $tmp;
                unset($tmp);
                // count addtions...
            } elseif ($authordata[$i]->type == 1) {
                $counts->adds++;
                $tmp->rev = $row['revision'];
                $tmp->size = abs($row['change']);
                $counts->additions[] = $tmp;
                unset($tmp);
                $j = $i;
                //... and check whether there has been a similar deletion
                do {
                    $j--;if ($j<0){break;}
                    //only look within the last 2 minutes of changes
                    if (round(abs($authordata[$i]->timestamp - $authordata[$j]->timestamp) / 60, 2) > 1) {
                        break;
                    }
                    //if same amount of text has been added as deletion, we 
                    //count it as a cut and paste action
                    if ((abs($authordata[$j]->change) == abs($authordata[$i]->change)) && ($authordata[$j]->type == 0)) {
                        $counts->cutandpaste++;
                    }
                } while ($j > 0);
            }
            $i++;
        }
    }
    $authorstore[$author]->pad->$pad->cuts = ($counts->dels - $counts->cutandpaste);
    $authorstore[$author]->pad->$pad->cutdetails = $counts->removes;
    $authorstore[$author]->pad->$pad->pastes = ($counts->adds - $counts->cutandpaste);
    $authorstore[$author]->pad->$pad->pastedetails = $counts->additions;
    $authorstore[$author]->pad->$pad->move = ($counts->cutandpaste);
    $authorstore[$author]->pad->$pad->charsadded = $counts->totaladds;
    $authorstore[$author]->pad->$pad->charsremoved = $counts->totaldels;
    $authorstore[$author]->pad->$pad->formats = $counts->totalformats;
}

/**
 * copies data from the etherpad-lite store into the various tables 
 */
function parseData() {
    global $link;
    $query = "SELECT * FROM store WHERE store.key LIKE 'pad:%';";
    $result = mysql_query($query,$link) or die("Cannot run query $query .");
    $num = mysql_numrows($result);
    $i = 0;
    while ($i < $num) {
        $key = explode(":", utf8_encode(mysql_result($result, $i, 'key')));
        $value = json_decode(utf8_encode(mysql_result($result, $i, 'value')));
        if ($value == null) {
            echo "ERROR DECODING JSON: ";
            print_r(mysql_result($result, $i, 'value'));
            die;
        }
        if (count($key) == 4) {
            switch ($key[2]) {
                case "chat": parseChatMessage($key, $value);
                    break;
                case "revs": parseRevision($key, $value);
                    break;
            }
        };
        if (count($key) == 2) {
            
            parsePad($key, $value);
        }
        $i++;
    }
}

/**
 * takes a store-row describing pad data and puts it in the pad table
 * @param type $key
 * @param type $value 
 */
function parsePad($key, $value) {
    global $link;
    $padData->text = $value->atext->text;
    $attribs = $value->pool->numToAttrib;
    $padData->revisions = $value->head;
    $padData->chatlines = $value->chatHead + 1;
    $padData->length = strlen($padData->text);
    mysql_query("INSERT INTO pad (idpad, revisions, chatlines, length) VALUES 
            ('$key[1]','$padData->revisions', '$padData->chatlines', '$padData->length');",$link)
            or die(mysql_error());
    foreach ($attribs as $item) {
        if ($item[0] == 'author') {
            mysql_query("INSERT INTO padauthors (idpad, idauthor) VALUES 
                    ('$key[1]', '$item[1]');",$link) or die(mysql_error());
        };
    }
}

/**
 * takes a store-row describing chat data and puts it in the chat table
 * @param type $key
 * @param type $value 
 */
function parseChatMessage($key, $value) {
    global $link;
    $chat->id = $key[3];
    $chat->idpad = $key[1];
    $chat->author = $value->userId;
    $chat->timestamp = $value->time;
    $chat->length = strlen($value->text);
    mysql_query("INSERT INTO chat (idchat, idpad, idauthor, `timestamp`, length) VALUES
        ($chat->id, '$chat->idpad', '$chat->author', FROM_UNIXTIME($chat->timestamp DIV 1000), $chat->length);",$link)
            or die(mysql_error());
}

/**
 * takes a store-row describing revision data and puts it in the changeset table
 * @param type $key
 * @param type $value 
 */
function parseRevision($key, $value) {
    global $link;
    // startlength, endlength, change, type, 
    $ref->idauthor = $value->meta->author;
    $ref->idpad = $key[1];
    $ref->idchangeset = $key[3];
    $ref->timestamp = $value->meta->timestamp;

    preg_match("/[0-9a-zA-Z]+/", $value->changeset, $matches, 0, 2);
    $ref->startlength = base_convert($matches[0], 36, 10);
    preg_match("/[0-9a-zA-Z]+/", $value->changeset, $matches, 0, 3 + strlen($matches[0]));
    $ref->change = base_convert($matches[0], 36, 10);

    $matches = substr(strpbrk($value->changeset, "<>="), 0, 1);
    switch ($matches) {
        case "<": $ref->type = 0;

            $ref->endlength = $ref->startlength - $ref->change;
            $ref->change = $ref->change * (-1);
            break;
        case ">": $ref->type = 1;
            $ref->endlength = $ref->startlength + $ref->change;
            break;
        case "=": $ref->type = 2;
            break;
    }
    mysql_query("INSERT INTO changeset (idpad,revision,startlength,endlength,`change`,timestamp,type,idauthor)
        VALUES ('$ref->idpad','$ref->idchangeset','$ref->startlength','$ref->endlength','$ref->change',FROM_UNIXTIME($ref->timestamp DIV 1000),'$ref->type','$ref->idauthor');",$link) or die(mysql_error());
}


/**
 * creates a plot using google charts with the number of added and removed
 * characters per day. This allows to understand how the different authors
 * distributed their work, and also allows to guess who wrote a lot and who was 
 * mainly correcting
 * @param type $pad the pad name which should be plotted
 * @param array $authornames of objects with name and color as properties
 */
function plotPadChar($pad, $authornames) {
    $data = NULL;
    $authornames['empty'] = (object) array('name' => 'empty', 'color' => '#000000');
    $query = "SELECT author.name AS author, author.idauthor AS id, timestamp AS date , sum( changeset.change ) AS num_chars
FROM changeset, author WHERE idpad = '$pad' AND changeset.idauthor = author.idauthor GROUP BY date_format( Date, '%m-%d' ) , author";
    $result = mysql_query($query) or die(mysql_error());
    $previous = null;

    //get start and end date
    try {
        $row = mysql_fetch_object($result);
    } catch (Exception $e) {
        echo $e . " during analysis of dates for chart.";
        die;
    }
    $start = $row->date;
    $end = $row->date;
    while ($row = mysql_fetch_object($result)) {
        if ($row->date < $start) {
            $start = $row->date;
        } elseif ($row->date > $end) {
            $end = $row->date;
        }
    } 
	// create structure to store actual data
    while($start<=$end) {
        foreach ($authornames as $author => $name) {
            $data[date("m-d", strtotime($start))]["$name->name"] = 0;
        }
       $start = date( 'Y-m-d H:i:s',(strtotime($start)+(24*60*60)));
    }
	//repeat to ensure most current data is there
    foreach ($authornames as $author => $name) {
            $data[date("m-d", strtotime($start))]["$name->name"] = 0;
        }
    $start = date( 'Y-m-d H:i:s',(strtotime($start)+(24*60*60)));
    
	// fill structure with actual data
    mysql_data_seek($result, 0);
    while ($row = mysql_fetch_object($result)) {
        $data[date("m-d", strtotime($row->date))][$row->author] = $row->num_chars;
    }
	
    $colors = "[";
    foreach ($authornames as $author => $name) {
        $colors.="{color: '$name->color'},";
    }
    $colors = substr_replace($colors, "]", -1);
  
    echo'
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages: ["corechart"]});
    </script>
    <script type="text/javascript">
      function drawVisualization() {
        // Create and populate the data table.
        var data = google.visualization.arrayToDataTable([
    ["Date"';
    foreach ($authornames as $id => $name) {
		if($name->name=='empty') continue;
        echo ',"' . $name->name . '"';
    } echo "],";
    $output = "";
    foreach ($data as $date => $author) {
        $output.= "['$date'";
        foreach ($author as $name => $clicks) {
			if($name=='empty') continue;
            $output.= ",$clicks";
        }
        $output.= "],";
    }
    echo substr_replace($output, "", -1);
    
    echo <<<END
    ]);  
        // Create and draw the visualization.
        new google.visualization.LineChart(document.getElementById('$pad')).draw(data, {curveType: "function",
                        height: 200, width:400,
                        series:$colors,
                        theme: 'maximized'}
                );
	    new google.visualization.ColumnChart(document.getElementById('padColumnChart')).draw(data, {curveType: "function",
                        height: 200, width:400,
                        series:$colors,
						theme: 'maximized'
                        }
                );
      } 
      google.setOnLoadCallback(drawVisualization);
    </script><BR>
         <!--<div id="$pad"></div>
		 <BR/><BR/>
		 <div id="padColumnChart"></div>-->
		 
		<div style="width:100%; height:100%;">			
			<div id="$pad" style="width:50%; height:95%; float:left;"></div>			
			<div id="padColumnChart" style="width:50%; height:95%; float:right;"></div>	    
		</div>
END;
    unset($data);
    
}
