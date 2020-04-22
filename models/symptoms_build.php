<?php

require_once(dirname(__FILE__).'/../library/database.php');
$output="<?php\n";
$symptoms=database::select_all("select * from symptoms");
$output.="\$symptoms=".var_export($symptoms,true).";\n";

$symptom_map=[];
foreach($symptoms as $i=>$symptom)
	$symptom_map[$symptom->column_name]=$i;
$output.="\$symptom_map=".var_export($symptom_map,true).";\n";

file_put_contents('symptoms.php',$output);