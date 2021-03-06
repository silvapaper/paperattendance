<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *
*
* @package    local
* @subpackage paperattendance
* @copyright  2016 Hans Jeria (hansjeria@gmail.coml) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('CLI_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->libdir . '/clilib.php'); 

global $DB;

// Now get cli options
list($options, $unrecognized) = cli_get_params(
		array('help'=>false),
        array('h'=>'help')
		);
if($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
// Text to the paperattendance console
if($options['help']) {
	$help =
	// Todo: localize - to be translated later when everything is finished
	"Process pdf located at folder unread on moodle file system.
	Options:
	-h, --help            Print out this help
	Example:
	\$sudo /usr/bin/php /local/paperattendance/cli/processpdf.php";
	echo $help;
	die();
}
//heading
cli_heading('Paper Attendance pdf processing'); // TODO: localize
echo "\nSearching for unread pdfs\n";
echo "\nStarting at ".date("F j, Y, G:i:s")."\n";

$initialtime = time();
$read = 0;
$found = 0;

// Sql that brings the unread pdfs names
$sqlunreadpdfs = "SELECT  id, pdf AS name, courseid
	FROM {paperattendance_session}
	WHERE status = ?
	ORDER BY lastmodified ASC";

// Parameters for the previous query
$params = array(PAPERATTENDANCE_STATUS_UNREAD);

// Read the pdfs if there is any unread, with readpdf function
if($resources = $DB->get_records_sql($sqlunreadpdfs, $params)){
	$path = $CFG -> dataroot. "/temp/local/paperattendance/unread";
	foreach($resources as $pdf){
		$found++;
		$process = paperattendance_readpdf($path, $pdf-> name, $pdf->courseid);
		if($process){
			$read++;
			$pdf->status = 1;
			$DB->update_record("paperattendance_session", $pdf);
		}
	}
	
	echo $found." PDF found. \n";
	echo $read." PDF processed. \n";
	
	// Displays the time required to complete the process
	$finaltime = time();
	$executiontime = $finaltime - $initialtime;
	
	echo "Execution time: ".$executiontime." seconds. \n";
}else{
	echo $found." pdfs found. \n";
}

exit(0);