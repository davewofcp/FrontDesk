<?php
require('barcodepdf.php');

$pdf=new PDF_EAN13('L','mm',array(27,90));
$pdf->AddPage();
$pdf->SetFont('Arial','',8);
$pdf->Text(4,3,"Computer Answers");
$pdf->Text(4,6,"Free Estimates/Diagnosis");
$pdf->Text(4,9,$storephn);
$pdf->Text(5,12,$pdf->EAN13(5,10,$issue_id));
$pdf->SetFont('Arial','',8);
$pdf->Text(38,5,"Intake: ".$fulldate);
$pdf->Text(38,9,"Customer Name: ".$customer_name);
$pdf->Text(38,13,$customer_email);
$pdf->Text(38,17,"Home: ".$homephone . "    Cell: ".$cellphone);
$pdf->Text(38,21,"Has Charger ??  " . $hascharger);
$pdf->Output();
?>