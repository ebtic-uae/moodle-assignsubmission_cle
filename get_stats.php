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
 
require_once('../../../../config.php');

require_once("etherpad-lite-client.php");
$authorstore = array();

echo "<H1>Pad Statistics</H1>";

connectDB();
makeTables();
getAuthorData();
parseData_all();

$pad = $_REQUEST['pad'];
$where = "";
if ($pad) {
    $where = "where idpad='$pad'";
}
echo "<H2> General Data</H2>";
$query = "SELECT name as number FROM author";
$result = mysql_query($query);
echo "Number of authors: " . mysql_numrows($result) . "<BR>";
$query = "SELECT idpad as number FROM pad";
$result = mysql_query($query);
echo "Number of pads: " . mysql_numrows($result) . "<BR>";
$query = "SELECT * FROM pad " . $where;
$result = mysql_query($query);
while ($row = mysql_fetch_assoc($result)) {
    //collect pad data
    $query = "SELECT timestamp from changeset where idpad = '" . $row['idpad'] . "' and revision = 0";
    $result2 = mysql_query($query) or die(mysql_error());
    $row2 = mysql_fetch_assoc($result2);
    unset($authornames);
    echo"<H2>Pad " . $row['idpad'] . " </H2>";
    echo "Time created: " . $row2['timestamp'] . "<br>";
    $query = "SELECT timestamp from changeset where idpad = '" . $row['idpad'] . "' and revision = " . $row['revisions'];
    $result2 = mysql_query($query) or die(mysql_error());
    $row2 = mysql_fetch_assoc($result2);
    echo "Last Change: " . $row2['timestamp'] . "<br>";
    echo "Revisions: " . $row['revisions'] . "<br>";
    echo "Chatlines: " . $row['chatlines'] . "<br>";
    echo "Length: " . $row['length'] . "<br>";

    //collect author data
    $query = "SELECT * FROM author,padauthors where padauthors.idpad='" . $row['idpad'] . "' AND author.idauthor = padauthors.idauthor";
    $result2 = mysql_query($query) or die(mysql_error());
    $i = 0;
    while ($row2 = mysql_fetch_assoc($result2)) {
        $authornames[$row2['idauthor']]->name = $row2['name'];
        $authornames[$row2['idauthor']]->color = $row2['color'];
        authorDetails_all($row['idpad'], $row2['idauthor']);
    }
    mysql_data_seek($result2, 0);
    $tableauthors = array();
    while ($row2 = mysql_fetch_assoc($result2)) {
        $tableauthors[] = $row2['idauthor'];
    }
    echo"<H3> Author details</H3>";
    echo"<table>";
    echo "<col>";
    foreach ($tableauthors as $authorid) {
        echo"<col style ='background-color:" . $authorstore[$authorid]->color . ";'>";
    }
    echo"<TR><TH>Name</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . $authorstore[$authorid]->name . "</TD>";
    }
    echo "</TR>";
    echo"<TR><TH>Final Contribution</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . ($authorstore[$authorid]->pad->$row['idpad']->charsadded - $authorstore[$authorid]->pad->$row['idpad']->charsremoved) . "</TD>";
    }
    echo "</TR>";
    echo"<TR><TH>Chars added/deleted</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . $authorstore[$authorid]->pad->$row['idpad']->charsadded . " / " . $authorstore[$authorid]->pad->$row['idpad']->charsremoved . "</TD>";
    }
    echo "</TR>";
    echo"<TR><TH>Inserts/Deletes/Moves</TH>";
    foreach ($tableauthors as $authorid) {
        echo"<TD>" . $authorstore[$authorid]->pad->$row['idpad']->pastes . " / " . $authorstore[$authorid]->pad->$row['idpad']->cuts . " / " . $authorstore[$authorid]->pad->$row['idpad']->move;
        echo"</TD>";
    }
    echo "</TR>";
    echo"</table>";
    plotPadChar_all($row['idpad'], $authornames);
}
echo "</BODY>";
mysql_close();

/**
 * creates a plot using google charts with the number of added and removed
 * characters per day. This allows to understand how the different authors
 * distributed their work, and also allows to guess who wrote a lot and who was 
 * mainly correcting
 * @param type $pad the pad name which should be plotted
 * @param array $authornames of objects with name and color as properties
 */
function plotPadChar_all($pad, $authornames) {
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
    while($start<$end) {
        foreach ($authornames as $author => $name) {
            $data[date("m-d", strtotime($start))]["$name->name"] = 0;
        }
       $start = date( 'Y-m-d H:i:s',(strtotime($start)+(24*60*60)));
    }

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
        echo ',"' . $name->name . '"';
    } echo "],";
    $output = "";
    foreach ($data as $date => $author) {
        $output.= "['$date'";
        foreach ($author as $name => $clicks) {
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
      } 
      google.setOnLoadCallback(drawVisualization);
    </script><BR>
         <div id="$pad"></div>
END;
    unset($data);
}

/**
 * collect data to present about each author.
 * @param type $pad
 * @param type $author 
 */
function authorDetails_all($pad, $author) {
    global $authorstore;
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
    $result = mysql_query($query) or die(mysql_error());
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
                    $j--;
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
 * creates a proper relational database of the etherpad-lite key-value
 * pair dump in mysql. The function always re-creates the tables
 * author, chat, pad, changeset, and padauthors (to link pad and authors)
 */
function makeTables() {
    $query = "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `etherpad` ;
USE `etherpad` ;

-- -----------------------------------------------------
-- Table `etherpad`.`author`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`author` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`author` (
  `idauthor` VARCHAR(18) NOT NULL ,
  `name` VARCHAR(45) NULL DEFAULT NULL ,
  `color` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`idauthor`) );


-- -----------------------------------------------------
-- Table `etherpad`.`changeset`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`changeset` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`changeset` (
  `idpad` VARCHAR(36) NOT NULL ,
  `revision` INT UNSIGNED NOT NULL ,
  `startlength` INT UNSIGNED NULL DEFAULT NULL ,
  `endlength` INT UNSIGNED NULL DEFAULT NULL ,
  `change` INT NULL DEFAULT NULL ,
  `timestamp` TIMESTAMP NULL DEFAULT NULL ,
  `type` TINYINT NULL DEFAULT NULL ,
  `idauthor` VARCHAR(18) NULL DEFAULT NULL ,
  PRIMARY KEY (`revision`, `idpad`) ,
  INDEX `fk_changeset_author1` (`idauthor` ASC) ,
  CONSTRAINT `fk_changeset_author1`
    FOREIGN KEY (`idauthor` )
    REFERENCES `etherpad`.`author` (`idauthor` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `etherpad`.`chat`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`chat` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`chat` (
  `idchat` INT NOT NULL ,
  `idpad` VARCHAR(36) NOT NULL ,
  `idauthor` VARCHAR(18) NULL DEFAULT NULL ,
  `timestamp` TIMESTAMP NULL DEFAULT NULL ,
  `length` INT NULL DEFAULT NULL ,
  PRIMARY KEY (`idchat`, `idpad`) ,
  INDEX `fk_chat_author` (`idauthor` ASC) ,
  CONSTRAINT `fk_chat_author`
    FOREIGN KEY (`idauthor` )
    REFERENCES `etherpad`.`author` (`idauthor` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `etherpad`.`pad`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`pad` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`pad` (
  `idpad` VARCHAR(36) NOT NULL ,
  `revisions` INT NULL DEFAULT NULL ,
  `chatlines` INT NULL DEFAULT NULL ,
  `length` INT NULL DEFAULT NULL ,
  PRIMARY KEY (`idpad`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `etherpad`.`padauthors`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`padauthors` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`padauthors` (
  `idpad` VARCHAR(36) NOT NULL ,
  `idauthor` VARCHAR(18) NOT NULL ,
  PRIMARY KEY (`idpad`, `idauthor`) ,
  INDEX `fk_padauthors_pad1` (`idpad` ASC) ,
  INDEX `fk_padauthors_author1` (`idauthor` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `etherpad`.`chunks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `etherpad`.`chunks` ;

CREATE  TABLE IF NOT EXISTS `etherpad`.`chunks` (
  `idchunks` INT NOT NULL ,
  `author` VARCHAR(18) NULL ,
  `pad` VARCHAR(36) NULL ,
  `length` INT NULL ,
  `content` MEDIUMTEXT NULL ,
  PRIMARY KEY (`idchunks`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

";
    $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);
    foreach ($queries as $query) {
        if (strlen(trim($query)) > 0)
            mysql_query($query) or die(" Error creating tables with " . mysql_error());
    }
}

/**
 * gets author data out of the etherpad-lite database and saves it in a table 
 * "author" with fields name, id,color 
 */
function getAuthorData() {
	
    global $authorstore;
    //colorpalette that etherpad-lite assigns to new authors. To 
    //match the statistics with the actual pad colors.
    $colorPalette = array("#ffc7c7", "#fff1c7", "#e3ffc7", "#c7ffd5",
        "#c7ffff", "#c7d5ff", "#e3c7ff", "#ffc7f1", "#ff8f8f", "#ffe38f",
        "#c7ff8f", "#8fffab", "#8fffff", "#8fabff", "#c78fff", "#ff8fe3",
        "#d97979", "#d9c179", "#a9d979", "#79d991", "#79d9d9", "#7991d9",
        "#a979d9", "#d979c1", "#d9a9a9", "#d9cda9", "#c1d9a9", "#a9d9b5",
        "#a9d9d9", "#a9b5d9", "#c1a9d9", "#d9a9cd", "#4c9c82", "#12d1ad",
        "#2d8e80", "#7485c3", "#a091c7", "#3185ab", "#6818b4", "#e6e76d",
        "#a42c64", "#f386e5", "#4ecc0c", "#c0c236", "#693224", "#b5de6a",
        "#9b88fd", "#358f9b", "#496d2f", "#e267fe", "#d23056", "#1a1a64",
        "#5aa335", "#d722bb", "#86dc6c", "#b5a714", "#955b6a", "#9f2985",
        "#4b81c8", "#3d6a5b", "#434e16", "#d16084", "#af6a0e", "#8c8bd8");
    $query = "SELECT * FROM store WHERE store.key LIKE 'globalAuthor:%'";
	
    $authors = mysql_query($query) or die("Cannot run query $query .");
    $num = mysql_numrows($authors);
    $i = 0;
    while ($i < $num) {
        $author = mysql_result($authors, $i, "key");
        $author = substr($author, 13);
        $authordata = json_decode(utf8_encode(mysql_result($authors, $i, "value")));
        if (is_int($authordata->colorId)) {

            $authordata->colorId = $colorPalette[$authordata->colorId];
        }
		
		$sql="INSERT INTO author (name, idauthor, color) VALUES('$authordata->name','$author','$authordata->colorId')";
		
        mysql_query("INSERT INTO author (name, idauthor, color) VALUES('$authordata->name','$author','$authordata->colorId')")
                or die("Error inserting author data with error " . mysql_error());
        $authorstore[$author]->name = $authordata->name;
        $authorstore[$author]->color = $authordata->colorId;

        $i++;
    }
    //FIXME: ugly hack to make sure it works with empty authors
    mysql_query("INSERT INTO author (name, idauthor, color) VALUES('empty','','#0000')")
            or die("Error inserting author data with error " . mysql_error());
}

/**
 * copies data from the etherpad-lite store into the various tables 
 */
function parseData_all() {
    $query = "SELECT * FROM store WHERE store.key LIKE 'pad:%';";
    $result = mysql_query($query) or die("Cannot run query $query .");
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
                case "chat": parseChatMessage_all($key, $value);
                    break;
                case "revs": parseRevision_all($key, $value);
                    break;
            }
        };
        if (count($key) == 2) {
            parsePad_all($key, $value);
        }
        $i++;
    }
}

/**
 * takes a store-row describing pad data and puts it in the pad table
 * @param type $key
 * @param type $value 
 */
function parsePad_all($key, $value) {
    $padData->text = $value->atext->text;
    $attribs = $value->pool->numToAttrib;
    $padData->revisions = $value->head;
    $padData->chatlines = $value->chatHead + 1;
    $padData->length = strlen($padData->text);
    mysql_query("INSERT INTO pad (idpad, revisions, chatlines, length) VALUES 
            ('$key[1]','$padData->revisions', '$padData->chatlines', '$padData->length');")
            or die(mysql_error());
    foreach ($attribs as $item) {
        if ($item[0] == 'author') {
            mysql_query("INSERT INTO padauthors (idpad, idauthor) VALUES 
                    ('$key[1]', '$item[1]');") or die(mysql_error());
        };
    }
}

/**
 * takes a store-row describing chat data and puts it in the chat table
 * @param type $key
 * @param type $value 
 */
function parseChatMessage_all($key, $value) {
    $chat->id = $key[3];
    $chat->idpad = $key[1];
    $chat->author = $value->userId;
    $chat->timestamp = $value->time;
    $chat->length = strlen($value->text);
    mysql_query("INSERT INTO chat (idchat, idpad, idauthor, `timestamp`, length) VALUES
        ($chat->id, '$chat->idpad', '$chat->author', FROM_UNIXTIME($chat->timestamp DIV 1000), $chat->length);")
            or die(mysql_error());
}

/**
 * takes a store-row describing revision data and puts it in the changeset table
 * @param type $key
 * @param type $value 
 */
function parseRevision_all($key, $value) {
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
        VALUES ('$ref->idpad','$ref->idchangeset','$ref->startlength','$ref->endlength','$ref->change',FROM_UNIXTIME($ref->timestamp DIV 1000),'$ref->type','$ref->idauthor');") or die(mysql_error());
}

/**
 * data to connect to the etherpad database. 
 */
function connectDB() {
	$host = get_config('assignsubmission_cle', 'etherpad_server');
	$database = get_config('assignsubmission_cle', 'etherpad_db');
	$username = get_config('assignsubmission_cle', 'etherpad_user');
	$password = get_config('assignsubmission_cle', 'etherpad_password');
	
    $link = mysql_connect($host, $username, $password) or die("Cannot connect to Server");
    mysql_select_db($database, $link) or die("Unable to select database");
}

?>
