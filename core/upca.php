<?php

REQUIRE_ONCE('../config.php'); // get timestamp from central config file
//date_default_timezone_set("America/New_York"); // got from ../config.php

require_once('BCGFontFile.php');
require_once('BCGColor.php');
require_once('BCGDrawing.php');
require_once('BCGupca.barcode.php');

$text = isset($_GET["barcode"]) ? $_GET["barcode"] : "000000000000";

$colorFront = new BCGColor(0, 0, 0);
$colorBack = new BCGColor(255, 255, 255);

$font = new BCGFontFile('Arial.ttf', 16);

$code = new BCGupca(); // Or another class name from the manual
$code->setScale(3); // Resolution
$code->setThickness(30); // Thickness
$code->setForegroundColor($colorFront); // Color of bars
$code->setBackgroundColor($colorBack); // Color of spaces
$code->setFont($font); // Font (or 0)
$code->parse($text); // Text

$drawing = new BCGDrawing('', $colorBack);
$drawing->setBarcode($code);
$drawing->draw();

header('Content-Type: image/png');

$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);

?>