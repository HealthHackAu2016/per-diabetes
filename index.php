<!DOCTYPE HTML>  
<html>
<head>
<style>
.error {color: #FF0000;}
</style>
</head>
<body>  

<?php
// define variables and set to empty values
$eGFRErr = "";
$eGFR = $previousHeartFailure = "";
$
$searchResults = "";
$doSearch = true;

// conect to database
// my god
  $servername = "localhost";
  $username = "root";
  $password = "healthhack";
  $dbname = "HEALTHHACK_GP";

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  } 
  
    $sql = "SELECT generic_name FROM DRUG_PROPERTIES";
    $result = $conn->query($sql);

    $drugList[] = "";

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            $drugList[] = $row["generic_name"];
        }
    }

  
  
    error_reporting(E_ALL);
 ini_set('display_errors', 1);
  

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  if (empty($_POST["eGFR"])) {
    $eGFRErr = "eGFR Score Required";
    $doSearch = false;
  } else {
    $eGFR = test_input($_POST["eGFR"]);
    // check if eGFR is a number
    if (!ctype_digit($eGFR)) {
      $eGFRErr = "Invalid eGFR score"; 
      $doSearch = false;
    }
  }
   
  if (empty($_POST["previousHeartFailure"])) {
    $previousHeartFailure = "false";
  } else {
    $previousHeartFailure = test_input($_POST["previousHeartFailure"]);
  }
  if (empty($_POST['maxDosage0'])) {
    $maxDosage0 = "false";
  }
  else {
    $maxDosage0 = "true";
  }

  //hey
  if ($doSearch) {
    $previousHeartFailure = $_POST["previousHeartFailure"];
    $e_GFR = $_POST["eGFR"];

    for ($i = 0; $i < 3; $i++) {
      if(empty($_POST['currentDrug'.$i]))
        $currentMedication[] = "";
      else
        $currentMedication[] = $_POST['currentDrug'.$i];
      if(empty($_POST['maxDosage'.$i]))
        $maxDosage[] = "";
      else
        $maxDosage[] = $_POST['maxDosage'.$i];
      if(empty($_POST['invalidDrug'.$i]))
        $InvalidMedication[] = "";
      else
        $InvalidMedication[] = $_POST['invalidDrug'.$i];
    }
      

    $joinedTables = "(DRUG_GROUPS INNER JOIN DRUG_PROPERTIES ON DRUG_GROUPS.group_name=DRUG_PROPERTIES.group_name)";

    $sql = "SELECT * FROM ". $joinedTables . " WHERE";
    //$sql_1 = "";
    $sql_2 = "";
    $i;

    if ($previousHeartFailure == "true") {
      //$sql_1 .= " WHERE CVD_rating <= 2 AND";
      $sql_2 .= " CVD_rating <= 2 AND";
    }
    

    if ($e_GFR < 60){
      //$sql_1 .= " WHERE '".$e_GFR."' >= eGFR_contraindicated_cutoff AND";
      $sql_2 .= " ".$e_GFR." >= eGFR_contraindicated_cutoff AND";
    }

    //work out iterations through array
    
    for ($i = 0; $i < sizeof($currentMedication); $i++){
      if ($maxDosage[$i] = "true" && strlen($currentMedication[$i]) > 0){
          //$sql_1 .= " WHERE generic_name != '" . $currentMedication[$i] . "' AND";
          $sql_2 .= " DRUG_PROPERTIES.group_name != (select group_name from DRUG_PROPERTIES WHERE generic_name = '" . $currentMedication[$i] . "') AND";  
      }
      //$sql_1 .= " DRUG_PROPERTIES.group_name = "(select group_name from DRUG_PROPERTIES WHERE generic_name = '" . $currentMedication[$i] . "')" NOT LIKE illegal_combination AND";
    }

    //work out iterations through array
    for ($i = 0; $i < sizeof($InvalidMedication); $i++){
      if (strlen($InvalidMedication[$i]) > 0) {
        //$sql_1 .= " WHERE generic_name != '" . $InvalidMedication[$i] . "' AND";
        $sql_2 .=  " DRUG_PROPERTIES.group_name != (select group_name from DRUG_PROPERTIES WHERE generic_name = '" . $InvalidMedication[$i] . "') AND";   
      }
      
    }
    
    $sql .= $sql_2 . " priority = (SELECT min(priority) FROM " . $joinedTables ." WHERE ". $sql_2 . " 1=1" .");";
    
    // sql query
    //echo "Jessql: ". $sql;
    
    $result = $conn->query($sql);

    $searchResults = "<h4><center><font color = 'red'>***check for drug interactions and other possible side-effects***</font></center></h4>";
    if ($result->num_rows > 0) {
        // do stuff to populate results
        //$searchResults = "You've got mail!";
        //# COMMON NAME # WARNINGS # POSSIBLE BENIFITS #
        
        $searchResults .= "<table style='width:100%''>";
        $searchResults .= "<tr>";
        $searchResults .= "<th>GENERIC NAME</th>";
        $searchResults .= "<th>DRUG GROUP</th>";
        $searchResults .= "<th>BENEFITS</th>";
        $searchResults .= "<th>RISKS</th>";
        $searchResults .= "</tr>";
        
        while($row = $result->fetch_assoc()) {
            $searchResults .= "<tr>";
            $searchResults .= "<td>".$row["generic_name"]."</td>";
            $searchResults .= "<td>".$row["group_name"]."</td>";
            if (!empty($row["general_note_positive"])){
              $searchResults .= "<td>".$row["general_note_positive"]."</td>";  
            }
            else {
              $searchResults .= "<td>N/A</td>";
            }
            if (!empty($row["general_note_negative"])){
              $searchResults .= "<td>".$row["general_note_negative"]."</td>";  
            }
            else {
              $searchResults .= "<td>N/A</td>";
            }
            $searchResults .= "</tr>";
        }
        
        $searchResults .= "</table>";
        
        
        //while($row = $result->fetch_assoc()) {
        //    $drugList[] = $row["generic_name"];
        //}

    }
    else {
        $searchResults = "No drugs can be recommended";
    }
    
  }
  
}
else {
  // if not post i.e. initial run
  $previousHeartFailure = "false";
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>



<h2>Diabetes Drug Recommendation</h2>
<p><span class="error">* required field.</span></p>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
  eGFR: <input type="text" name="eGFR" value="<?php echo $eGFR;?>">
  <span class="error">* <?php echo $eGFRErr;?></span>
  <br><br>
  Heart Failure History:
  <input type="radio" name="previousHeartFailure" <?php if (isset($previousHeartFailure) && $previousHeartFailure=="true") echo "checked";?> value="true">true
  <input type="radio" name="previousHeartFailure" <?php if (isset($previousHeartFailure) && $previousHeartFailure=="false") echo "checked";?> value="false">false
  <br><br>

<?php

  // create body and footer of select
  $drugSelectComponent = "";
  foreach ($drugList as $drugName) {
    $drugSelectComponent.='<option value="'.$drugName.'">'.$drugName.'</option>';
  }

  $selectCurrent="";
  for ($i = 0; $i < 3; $i++) {
    // dropdown list
    $selectCurrent.= '<select name="currentDrug'.$i.'">'.$drugSelectComponent .'</select>  ';
    $selectCurrent.= '<input type="checkbox" name="maxDosage'. $i.'" value="true" > Maximum Dosage';
    // new line
    $selectCurrent.= '<br><br>';
  }
  
   $selectAdverse="";
  for ($i = 0; $i < 3; $i++) {
    // dropdown list
    $selectAdverse.= '<select name="invalidDrug'.$i.'">'.$drugSelectComponent .'</select>  ';
    // new line
    $selectAdverse.= '<br><br>';
  }


  echo "Current Medication: <br><br>";
  echo $selectCurrent;
  echo "Known Medication Adversions: <br><br>";
  echo $selectAdverse;


?>  
 

  <input type="submit" name="submit" value="Search">  
</form>
<?php
if (!empty($searchResults)) {
  echo "<h2>Results:</h2>";
  echo $searchResults;  
}

?>

</body>
</html>