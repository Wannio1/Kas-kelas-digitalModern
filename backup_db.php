<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    die("Access denied");
}

// Variables $host, $username, $password, $dbname are imported from koneksi.php
// Connect using mysqli for dump
$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->select_db($dbname); 
$mysqli->query("SET NAMES 'utf8'");

$tables = array();
$result = $mysqli->query('SHOW TABLES');
while($row = $result->fetch_row()){
    $tables[] = $row[0];
}

$return = "";
foreach($tables as $table){
    $result = $mysqli->query('SELECT * FROM '.$table);
    $num_fields = $result->field_count;
    
    $return .= 'DROP TABLE '.$table.';';
    $row2 = $mysqli->query('SHOW CREATE TABLE '.$table);
    $row2 = $row2->fetch_row();
    $return .= "\n\n".$row2[1].";\n\n";
    
    for ($i = 0; $i < $num_fields; $i++) {
        while($row = $result->fetch_row()){
            $return.= 'INSERT INTO '.$table.' VALUES(';
            for($j=0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                if ($j < ($num_fields-1)) { $return.= ','; }
            }
            $return.= ");\n";
        }
    }
    $return.="\n\n\n";
}

$fileName = 'backup-kas-kelas-'.date('Y-m-d-H-i-s').'.sql';

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"".$fileName."\""); 
echo $return; 
exit;
?>

