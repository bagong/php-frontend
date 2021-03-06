<?php
require_once("_basic_tasks.php");
$url_this_page = "csound.php";

$autosave = TRUE;
// $autosave = FALSE;
$verbose = TRUE;
$verbose = FALSE;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "csound.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
echo "<p><small>Current directory = ".$dir;
echo "   <span id='message2' style=\"margin-bottom:1em;\"></span>";
echo "</small></p>";
echo link_to_help();

echo "<h3>Csound instrument file “".$filename."”</h3>";

if($test) echo "dir = ".$dir."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".session_id()."_temp";
// echo "temp_folder = ".$temp_folder."<br />";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}

if(isset($_POST['delete_instrument'])) {
	$instrument = $_POST['instrument_name'];
	echo "<p><font color=\"red\">Deleted </font><font color=\"blue\"><big>“".$instrument."”</big></font>…</p>";
	$this_instrument_file = $temp_dir.$temp_folder.SLASH.$instrument.".txt";
//	echo $this_instrument_file."<br />";
	rename($this_instrument_file,$this_instrument_file.".old");
	$number_instruments = $_POST['number_instruments'] - 1;
	$_POST['number_instruments'] = $number_instruments;
	}

if(isset($_POST['restore'])) {
	echo "<p><font color=\"red\">Restoring: </font>";
	$dircontent = scandir($temp_dir.$temp_folder);
	$number_instruments = 0;
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension == "old" OR $extension == "txt") $number_instruments++;
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		$this_instrument_file = $temp_dir.$temp_folder.SLASH.$oldfile;
		$new_name = str_replace(".old",'',$this_instrument_file);
		if(!file_exists($new_name)) {
			rename($this_instrument_file,$new_name);
			echo "“<font color=\"blue\">".str_replace(".txt",'',$thisfile)."</font>” ";
			}
		else echo "“<font color=\"blue\"><del>".str_replace(".txt",'',$thisfile)."</del></font>” ";
		}
	$_POST['number_instruments'] = $number_instruments;
	echo "</p>";
	}

$index_max = 0;
$deleted_instruments = '';
$exists_name = array();
$dircontent = scandir($temp_dir.$temp_folder);
foreach($dircontent as $oldfile) {
	if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
	$table = explode(".",$oldfile);
	$extension = end($table);
	if($extension == "old" OR $extension == "txt") {
		$content = @file_get_contents($temp_dir.$temp_folder.SLASH.$oldfile,TRUE);
		$table = explode(chr(10),$content);
		$index_this_instrument = $table[5];
		if($index_this_instrument > $index_max) $index_max = $index_this_instrument;
		}
	$thisfile = str_replace(".old",'',$oldfile);
	$this_instrument = str_replace(".txt",'',$thisfile);
	$exists_name[$this_instrument] = TRUE;
	if($extension == "old")
		$deleted_instruments .= "“".$this_instrument."” ";
	}

if(isset($_POST['create_instrument'])) {
	$new_index = $_POST['index_max'] + 1;
//	$new_index = $index_max + 1;
	$new_instrument = trim($_POST['new_instrument']);
	$new_instrument = str_replace(' ','_',$new_instrument);
	$new_instrument = str_replace('"','',$new_instrument);
	if(isset($exists_name[$new_instrument])) {
		echo "<p><font color=\"red\">An instrument named “<font color=\"blue\">".$new_instrument."</font>” already exists!</font></p>";
		}
	else if($new_instrument <> '') {
		$new_instrument_file = $temp_dir.$temp_folder.SLASH.$new_instrument.".txt";
		$template = "instrument_template";
		$template_content = @file_get_contents($template,TRUE);
		$template_content = str_replace("[[index]]",$new_index,$template_content);
		$handle = fopen($new_instrument_file,"w");
		$file_header = $top_header."\n// Object prototype saved as \"".$new_instrument."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$filename."\n");
		fwrite($handle,$template_content."\n");
		fclose($handle);
		$number_instruments = $_POST['number_instruments'] + 1;
		$_POST['number_instruments'] = $number_instruments;
		}
	}

if(isset($_POST['duplicate_instrument'])) {
	$instrument = $_POST['instrument_name'];
	$copy_instrument = trim($_POST['copy_instrument']);
	$copy_instrument = str_replace(' ','_',$copy_instrument);
	$copy_instrument = str_replace('"','',$copy_instrument);
	if(isset($exists_name[$copy_instrument])) {
		echo "<p><font color=\"red\">Cannot create</font> <font color=\"blue\"><big>“".$copy_instrument."”</big></font> <font color=\"red\">because an instrument with that name already exists</font></p>";
		unset($_POST['duplicate_instrument']);
		}
	else {
		$copy_instrument_file = $temp_dir.$temp_folder.SLASH.$copy_instrument.".txt";
		if($copy_instrument <> '') {
			$this_instrument_file = $temp_dir.$temp_folder.SLASH.$instrument.".txt";
			copy($this_instrument_file,$copy_instrument_file);
			@unlink($temp_dir.$temp_folder.SLASH.$copy_instrument.".txt.old");
			$number_instruments = $_POST['number_instruments'] + 1;
			$_POST['number_instruments'] = $number_instruments;
			}
		}
	}
	
if(isset($_POST['savealldata']) OR isset($_POST['delete_instrument']) OR isset($_POST['restore']) OR isset($_POST['create_instrument']) OR isset($_POST['duplicate_instrument'])) {
	echo "<p id=\"timespan\"><font color=\"red\">Saving file:</font> <font color=\"blue\">".$filename."</font>";
	SaveCsoundInstruments(FALSE,$dir,$filename,$temp_dir.$temp_folder);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$extract_data = extract_data(FALSE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savealldata\" value=\"SAVE ‘".$filename."’\"></p>";

if($autosave) {
	echo "<p><font color=\"red\">➡</font> This file is <font color=\"red\">autosaved</font> every 30 seconds. Keep this page open as long as you are editing instruments!</p>";
	echo "<script type=\"text/javascript\" src=\"autosaveInstruments.js\"></script>";
	}

echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"create_instrument\" value=\"CREATE A NEW INSTRUMENT\"> named <input type=\"text\" name=\"new_instrument\" size=\"20\" value=\"\"></p>";

$content_no_br = str_replace("<br>",chr(10),$content);
$table = explode(chr(10),$content_no_br);
$imax_file = count($table);
// Line 509 in SaveLoads1.c
if($verbose) echo "imax_file = ".$imax_file."<br />";
$number_channels = $table[0];
if($verbose) echo "number_channels = ".$number_channels."<br />";
echo "<input type=\"hidden\" name=\"number_channels\" value=\"".$number_channels."\">";

for($j = 0; $j < $number_channels; $j++) {
	$which = $table[$j + 1];
//	if($which < 0) continue;
	$ch = $j + 1;
	$whichCsoundInstrument[$j] = $which;
	if($verbose) echo "MIDI channel #".$ch." => instrument [index ".$whichCsoundInstrument[$j]."]<br />";
	}
$CsoundOrchestraName = preg_replace("/<\/?html>/u",'',$table[++$j]);
echo "Csound orchestra file = <input type=\"text\" name=\"CsoundOrchestraName\" size=\"30\" value=\"".$CsoundOrchestraName."\"> ➡ ";
$orchestra_filename = $dir.$CsoundOrchestraName;
// echo $orchestra_filename."<br />";
	$path = str_replace($bp_application_path,'',$dir);
if(file_exists($orchestra_filename)) {
	echo "<a target=\"_blank\" href=\"csorchestra.php?file=".urlencode($path.$CsoundOrchestraName)."\">edit this file</a>";
	}
else {
	echo "File not found: <a target=\"_blank\" href=\"csorchestra.php?file=".urlencode($path.$CsoundOrchestraName)."\">create it!</a>";
	}
echo "<br />";
$number_instruments = $table[++$j];
if($verbose) echo "number_instruments = ".$number_instruments."<br />";
echo "<input type=\"hidden\" name=\"number_instruments\" value=\"".$number_instruments."\">";
$i = $j;
$handle_instrument = FALSE;
$name_index = $index_name = array();
for($j = 0; $j < $number_instruments; $j++) {
	$CsoundInstrumentName[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
	$CsoundInstrumentName[$j] = str_replace(' ','_',$CsoundInstrumentName[$j]);
	if($verbose) echo "<br /><b>Instrument name = “".$CsoundInstrumentName[$j]."”</b><br />";
	// Create temporary file and folder for this instrument
	$filename_this_instrument = $CsoundInstrumentName[$j];
	$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$filename_this_instrument;
	if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
	$instrument_file[$j] = $folder_this_instrument.".txt";
	$argmax_file = $folder_this_instrument.SLASH."argmax.php";
	if($handle_instrument) fclose($handle_instrument);
	$handle_instrument = fopen($instrument_file[$j],"w");
	$file_header = $top_header."\n// Csound instrument saved as \"".$CsoundInstrumentName[$j]."\". Date: ".gmdate('Y-m-d H:i:s');
	$file_header .= "\n".$filename;
	fwrite($handle_instrument,$file_header."\n");
	$InstrumentComment[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
	if($verbose) echo "Instrument comment = “".$InstrumentComment[$j]."”<br />";
	fwrite($handle_instrument,$InstrumentComment[$j]."\n");
	$argmax[$j] = $table[++$i];
	$argmax_file = $folder_this_instrument.SLASH."argmax.php";
	if(!file_exists($argmax_file)) {
		$handle = fopen($argmax_file,"w");
		$text = "<xxxphp\n";
		$text .= "yyylast_argument[\"".$CsoundInstrumentName[$j]."\"] = ".$argmax[$j].";\n";
		$text .= "xxx>\n";
		$text = str_replace("xxx","?",$text);
		$text = str_replace("yyy","$",$text);
		fwrite($handle,$text);
		fclose($handle);
		}
	else {
		$instrument_argmax = max_argument($argmax_file);
		/* if($instrument_argmax > $argmax[$j]) */ $argmax[$j] = $instrument_argmax;
		}
	if($verbose) echo "argmax = ".$argmax[$j]."<br />";
	fwrite($handle_instrument,$argmax[$j]."\n");
	$argmax[$j] = 0;
	$InstrumentIndex[$j] = $table[++$i];
	if($InstrumentIndex[$j] > $index_max) $index_max = $InstrumentIndex[$j];
	$name_index[$CsoundInstrumentName[$j]] = $InstrumentIndex[$j];
	$index_name[$InstrumentIndex[$j]] = $CsoundInstrumentName[$j];
	if($verbose) echo "Instrument index = ".$InstrumentIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentIndex[$j]."\n");
	$InstrumentDilationRatioIndex[$j] = $table[++$i];
	if($InstrumentDilationRatioIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentDilationRatioIndex[$j];
	if($verbose) echo "Instrument dilation ratio argument = ".$InstrumentDilationRatioIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentDilationRatioIndex[$j]."\n");
	$InstrumentAttackVelocityIndex[$j] = $table[++$i];
	if($InstrumentAttackVelocityIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentAttackVelocityIndex[$j];
	if($verbose) echo "Instrument attack velocity argument = ".$InstrumentAttackVelocityIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentAttackVelocityIndex[$j]."\n");
	$InstrumentReleaseVelocityIndex[$j] = $table[++$i];
	if($InstrumentReleaseVelocityIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentReleaseVelocityIndex[$j];
	if($verbose) echo "Instrument release velocity argument = ".$InstrumentReleaseVelocityIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentReleaseVelocityIndex[$j]."\n");
	$InstrumentPitchIndex[$j] = $table[++$i];
	if($InstrumentPitchIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchIndex[$j];
	if($verbose) echo "Instrument pitch argument = ".$InstrumentPitchIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchIndex[$j]."\n");
	$InstrumentPitchFormat[$j] = $table[++$i];
	if($verbose) echo "Instrument pitch format = ".$InstrumentPitchFormat[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchFormat[$j]."\n");
	$InstrumentPitchBendRange[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend range = ".$InstrumentPitchBendRange[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendRange[$j]."\n");
	$InstrumentPitchBendIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend islogx = ".$InstrumentPitchBendIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendIsLogX[$j]."\n");
	$InstrumentPitchBendIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend islogy = ".$InstrumentPitchBendIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendIsLogY[$j]."\n");
	$InstrumentVolumeIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument volume islogx = ".$InstrumentVolumeIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeIsLogX[$j]."\n");
	$InstrumentVolumeIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument volume islogy = ".$InstrumentVolumeIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeIsLogY[$j]."\n");
	$InstrumentPressureIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument pressure islogx = ".$InstrumentPressureIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureIsLogX[$j]."\n");
	$InstrumentPressureIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument pressure islogy = ".$InstrumentPressureIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureIsLogY[$j]."\n");
	$InstrumentModulationIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument modulation islogx = ".$InstrumentModulationIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationIsLogX[$j]."\n");
	$InstrumentModulationIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument modulation islogy = ".$InstrumentModulationIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationIsLogY[$j]."\n");
	$InstrumentPanoramicIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramic islogx = ".$InstrumentPanoramicIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicIsLogX[$j]."\n");
	$InstrumentPanoramicIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramic islogy = ".$InstrumentPanoramicIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicIsLogY[$j]."\n");
	$InstrumentPitchbendStartIndex[$j] = $table[++$i];
	if($InstrumentPitchbendStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendStartIndex[$j];
	if($verbose) echo "Instrument pitchbend start argument = ".$InstrumentPitchbendStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendStartIndex[$j]."\n");
	$InstrumentVolumeStartIndex[$j] = $table[++$i];
	if($InstrumentVolumeStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeStartIndex[$j];
	if($verbose) echo "Instrument volume start argument = ".$InstrumentVolumeStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeStartIndex[$j]."\n");
	$InstrumentPressureStartIndex[$j] = $table[++$i];
	if($InstrumentPressureStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureStartIndex[$j];
	if($verbose) echo "Instrument pressure start argument = ".$InstrumentPressureStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureStartIndex[$j]."\n");
	$InstrumentModulationStartIndex[$j] = $table[++$i];
	if($InstrumentModulationStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationStartIndex[$j];
	if($verbose) echo "Instrument modulation start argument = ".$InstrumentModulationStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationStartIndex[$j]."\n");
	$InstrumentPanoramicStartIndex[$j] = $table[++$i];
	if($InstrumentPanoramicStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicStartIndex[$j];
	if($verbose) echo "Instrument panoramic start argument = ".$InstrumentPanoramicStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicStartIndex[$j]."\n");
	$InstrumentPitchbendEndIndex[$j] = $table[++$i];
	if($InstrumentPitchbendEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendEndIndex[$j];
	if($verbose) echo "Instrument pitchbend end argument = ".$InstrumentPitchbendEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendEndIndex[$j]."\n");
	$InstrumentVolumeEndIndex[$j] = $table[++$i];
	if($InstrumentVolumeEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeEndIndex[$j];
	if($verbose) echo "Instrument volume end argument = ".$InstrumentVolumeEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeEndIndex[$j]."\n");
	$InstrumentPressureEndIndex[$j] = $table[++$i];
	if($InstrumentPressureEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureEndIndex[$j];
	if($verbose) echo "Instrument pressure end argument = ".$InstrumentPressureEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureEndIndex[$j]."\n");
	$InstrumentModulationEndIndex[$j] = $table[++$i];
	if($InstrumentModulationEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationEndIndex[$j];
	if($verbose) echo "Instrument modulation end argument = ".$InstrumentModulationEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationEndIndex[$j]."\n");
	$InstrumentPanoramicEndIndex[$j] = $table[++$i];
	if($InstrumentPanoramicEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicEndIndex[$j];
	if($verbose) echo "Instrument panoramic end argument = ".$InstrumentPanoramicEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicEndIndex[$j]."\n");
	$InstrumentPitchbendTable[$j] = $table[++$i];
	if($InstrumentPitchbendTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendTable[$j];
	if($verbose) echo "Instrument pitchbend table = ".$InstrumentPitchbendTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendTable[$j]."\n");
	$InstrumentVolumeTable[$j] = $table[++$i];
	if($InstrumentVolumeTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeTable[$j];
	if($verbose) echo "Instrument volume table = ".$InstrumentVolumeTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeTable[$j]."\n");
	$InstrumentPressureTable[$j] = $table[++$i];
	if($InstrumentPressureTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureTable[$j];
	if($verbose) echo "Instrument pressure table = ".$InstrumentPressureTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureTable[$j]."\n");
	$InstrumentModulationTable[$j] = $table[++$i];
	if($InstrumentModulationTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationTable[$j];
	if($verbose) echo "Instrument modulation table = ".$InstrumentModulationTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationTable[$j]."\n");
	$InstrumentPanoramicTable[$j] = $table[++$i];
	if($InstrumentPanoramicTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicTable[$j];
	if($verbose) echo "Instrument panoramic table = ".$InstrumentPanoramicTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicTable[$j]."\n");
	$InstrumentPitchbendGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument pitchbendGEN = ".$InstrumentPitchbendGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendGEN[$j]."\n");
	$InstrumentVolumeGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument volumeGEN = ".$InstrumentVolumeGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeGEN[$j]."\n");
	$InstrumentPressureGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument pressureGEN = ".$InstrumentPressureGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureGEN[$j]."\n");
	$InstrumentModulationGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument modulationGEN = ".$InstrumentModulationGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationGEN[$j]."\n");
	$InstrumentPanoramicGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramicGEN = ".$InstrumentPanoramicGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicGEN[$j]."\n");
	for($ii = 0; $ii < 6; $ii++) {
		$InstrumentPitchbend[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Pitchbend[".$ii."] = ".$InstrumentPitchbend[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPitchbend[$j][$ii]."\n");
		$InstrumentVolume[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Volume[".$ii."] = ".$InstrumentVolume[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentVolume[$j][$ii]."\n");
		$InstrumentPressure[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Pressure[".$ii."] = ".$InstrumentPressure[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPressure[$j][$ii]."\n");
		$InstrumentModulation[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Modulation[".$ii."] = ".$InstrumentModulation[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentModulation[$j][$ii]."\n");
		$InstrumentPanoramic[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Panoramic[".$ii."] = ".$InstrumentPanoramic[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPanoramic[$j][$ii]."\n");
		}
//	echo $CsoundInstrumentName[$j]." argmax = ".$argmax[$j]."<br />";
	set_argmax_argument($argmax_file,$CsoundInstrumentName[$j],$argmax[$j]);
	
	// Line 655
	$Instrument_ipmax = $table[++$i];
	if($verbose) echo "Instrument ipmax = ".$Instrument_ipmax."<br />";
	for($ip = 0; $ip < $Instrument_ipmax; $ip++) {
		$empty_parameter = FALSE;
		// $folder_this_instrument
		$Instrument_paramlist_name = preg_replace("/<\/?html>/u",'',$table[++$i]);
		$Instrument_paramlist_name = str_replace(' ','_',$Instrument_paramlist_name);
		$handle_parameter = NULL;
		// Old ‘-cs’ files contain up to 6 parameters with empty names. We'll ignore them.
		$argmax_parameter = 0;
		if($Instrument_paramlist_name == '') $empty_parameter = TRUE;
		else {
			$filename_this_parameter = $Instrument_paramlist_name;
			$parameter_file = $folder_this_instrument."/".$filename_this_parameter.".txt";
			$handle_parameter = fopen($parameter_file,"w");
			}
		if($verbose) echo "<br /><font color=blue>Instrument paramlist name = “".$Instrument_paramlist_name."”<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_name."\n");
		// Line 687
		$Instrument_paramlist_comment = preg_replace("/<\/?html>/u",'',$table[++$i]);
		if($verbose) echo "Instrument paramlist comment = “".$Instrument_paramlist_comment."”</font><br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_comment."\n");
		$Instrument_paramlist_startindex = $table[++$i];
		if($Instrument_paramlist_startindex > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_startindex;
		if($verbose) echo "start argument = ".$Instrument_paramlist_startindex."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_startindex."\n");
		$Instrument_paramlist_endindex = $table[++$i];
		if($Instrument_paramlist_endindex > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_endindex;
		if($verbose) echo "end argument = ".$Instrument_paramlist_endindex."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_endindex."\n");
		// line 701
		$Instrument_paramlist_table = $table[++$i];
		if($Instrument_paramlist_table > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_table;
		if($verbose) echo "table argument = ".$Instrument_paramlist_table."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_table."\n");
		$Instrument_paramlist_defaultvalue = $table[++$i];
		if($verbose) echo "default value = ".$Instrument_paramlist_defaultvalue."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_defaultvalue."\n");
		$Instrument_paramlist_GENtype = $table[++$i];
		if($verbose) echo "GEN type = ".$Instrument_paramlist_GENtype."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_GENtype."\n");
		$Instrument_paramlist_combinationtype = $table[++$i];
		if($verbose) echo "combination type = ".$Instrument_paramlist_combinationtype."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_combinationtype."\n");
		if(!$empty_parameter) {
			fclose($handle_parameter);
			set_argmax_argument($argmax_file,$Instrument_paramlist_name,$argmax_parameter);
			}
		}
	// line 724
	}
if($handle_instrument) fclose($handle_instrument);
$begin_tables = $table[++$i];
if($verbose) echo "<br /><b>begin tables = “".$begin_tables."”</b><br />";
echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
echo "<input type=\"hidden\" name=\"index_max\" value=\"".$index_max."\">";

echo "<table style=\"background-color:white;\"><tr><td>";
echo "<h4 style=\"text-align:center;\">Tables</h4>";
echo "<textarea name=\"cstables\" rows=\"20\" style=\"width:400px; background-color:Cornsilk;\">";
$cstables = '';
for($i = $i + 1; $i < $imax_file; $i++) {
	$line = trim($table[$i]);
	if($line == '') continue;
	$cstables .= $line."\n";
	echo preg_replace("/<\/?html>/u",'',$line)."\n";
	}
echo "</textarea>";
echo "<p style=\"text-align:center;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savealldata\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</td><td>";
echo "<h4 style=\"text-align:center;\">MIDI channel association of instruments</h4>";
echo "<table>";
echo "<tr>";
echo "<td style=\"padding: 5px; vertical-align:middle;\">MIDI channel</td><td>Instrument index</td>";
echo "</tr>";
for($ch = 0; $ch < 16; $ch++) {
	echo "<tr>";
	echo "<td>".($ch + 1)."</td>";
	echo "<td style=\"padding: 5px; vertical-align:middle;\">";
	$arg = "whichCsoundInstrument_".$ch;
	$x = $whichCsoundInstrument[$ch];
	$name = '';
	if($x < 0) $x = '';
	else if(isset($index_name[$x])) $name = $index_name[$x];
	echo "<input type=\"text\" name=\"".$arg."\" size=\"4\" value=\"".$x."\"> <i>".$name."</i>";
	echo "</td>";
	echo "</tr>";
	}
echo "</table>";
echo "</td></tr></table>";

if($deleted_instruments <> '') echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"restore\" value=\"RESTORE ALL DELETED INSTRUMENTS\"> = <font color=\"blue\"><big>".$deleted_instruments."</big></font></p>";
echo "</form>";

if($number_instruments > 0) {
	echo "<h3>Click instruments below to edit them:</h3>";
	echo "<table>";
	for($j = 0; $j < $number_instruments; $j++) {
		echo "<tr><td style=\"padding: 5px; vertical-align:middle; white-space:nowrap\">";
		echo "<form method=\"post\" action=\"csinstrument.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"instrument_file\" value=\"".$instrument_file[$j]."\">";
		echo "<big>[".$name_index[$CsoundInstrumentName[$j]]."]</big> ";
		echo "<input style=\"background-color:azure; font-size:larger;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"instrument_name\" value=\"".$CsoundInstrumentName[$j]."\">";
		
		$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$CsoundInstrumentName[$j];
		$argmax_file = $folder_this_instrument.SLASH."argmax.php";
		$argmax_all = max_argument($argmax_file);
		
		echo "&nbsp;(".$argmax_all."&nbsp;args)";
		echo "</form>";
		echo "</td>";
		echo "<td style=\"vertical-align:middle;\">";
		echo "<small>".$InstrumentComment[$j]."</small>";
		echo "</td>";
		echo "<td style=\"padding: 5px; vertical-align:middle;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
		echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$CsoundInstrumentName[$j]."\">";
		echo "<input type=\"hidden\" name=\"number_channels\" value=\"".$number_channels."\">";
		echo "<input type=\"hidden\" name=\"CsoundOrchestraName\" value=\"".$CsoundOrchestraName."\">";
		echo "<input type=\"hidden\" name=\"number_instruments\" value=\"".$number_instruments."\">";
		echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
		echo "<input type=\"hidden\" name=\"cstables\" value=\"".$cstables."\">";
		for($ch = 0; $ch < $number_channels; $ch++) {
			$arg = "whichCsoundInstrument_".$ch;
			echo "<input type=\"hidden\" name=\"".$arg."\" value=\"".$whichCsoundInstrument[$ch]."\">";
			}
		echo "<input style=\"background-color:yellow; \" type=\"submit\" name=\"delete_instrument\" value=\"DELETE\">";
		echo "</td>";
		echo "<td style=\"text-align:right; padding:5px; vertical-align:middle;\">";
		echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"duplicate_instrument\" value=\"DUPLICATE AS\">: <input type=\"text\" name=\"copy_instrument\" size=\"15\" value=\"\">";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
		}
	echo "</table>";
	}

if($verbose) {
	echo "<hr>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	// echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
	echo "<textarea name=\"thistext\" rows=\"20\" style=\"width:700px; background-color:Cornsilk;\">".$content."</textarea>";
	echo "</form>";
	}
?>
