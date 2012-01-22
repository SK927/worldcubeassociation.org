<?php
#----------------------------------------------------------------------
#   Initialization and page contents.
#----------------------------------------------------------------------

require( '../_header.php' );

analyzeChoices();
saveChoices();
offerChoices();

require( '../_footer.php' );

#----------------------------------------------------------------------
function analyzeChoices () {
#----------------------------------------------------------------------
  global $chosenId, $chosenConfirm, $chosenName, $chosenNameHtml, $chosenCountryId, $chosenGender, $chosenDay, $chosenMonth, $chosenYear, $chosenUpdate, $chosenFix;

  $chosenConfirm     = getBooleanParam( 'confirm'   );
  $chosenUpdate      = getBooleanParam( 'update'    );
  $chosenFix         = getBooleanParam( 'fix'       );
  $chosenId          = getNormalParam ( 'id'        );
  $chosenName        = getMysqlParam  ( 'name'      );
  $chosenNameHtml    = getHtmlParam   ( 'name'      );
  $chosenCountryId   = getMysqlParam  ( 'countryId' );
  $chosenGender      = getNormalParam ( 'gender'    );
  $chosenDay         = getNormalParam ( 'day'       );
  $chosenMonth       = getNormalParam ( 'month'     );
  $chosenYear        = getNormalParam ( 'year'      );

}

#----------------------------------------------------------------------
function saveChoices () {
#----------------------------------------------------------------------
  global $chosenId, $chosenConfirm, $chosenName, $chosenNameHtml, $chosenCountryId, $chosenGender, $chosenDay, $chosenMonth, $chosenYear, $chosenUpdate, $chosenFix;

  if( $chosenFix ){

    #--- Change the Persons table
    dbCommand( "UPDATE Persons SET name='$chosenName', countryId='$chosenCountryId', gender='$chosenGender', year='$chosenYear', month='$chosenMonth', day='$chosenDay' WHERE id='$chosenId' AND subId='1'" );

    #--- Change the Results table if the name has been changed.
    $persons = dbQuery( "SELECT * FROM Persons WHERE id='$chosenId' AND subId=1" );
    if( count( $persons ) == 0 ){
      noticeBox(false, 'Unknown WCA Id '.$chosenId);
      return;
    }
    $person = array_shift( $persons );

    if( $person['name'] != $chosenName )
      dbCommand( "UPDATE Results SET personName='$chosenName' WHERE personId='$chosenId'" );

    noticeBox( true, "Successfully fixed $chosenNameHtml($chosenId).");

  }

  if( $chosenUpdate ){

    $persons = dbQuery( "SELECT * FROM Persons WHERE id='$chosenId' AND subId=1" );
    if( count( $persons ) == 0 ){
      noticeBox(false, 'Unknown WCA Id '.$chosenId);
      return;
    }

    $person = array_shift( $persons );

    if(( $person['name'] == $chosenName ) and ( $person['countryId'] == $chosenCountryId )){
      noticeBox(false, 'The name or the country must be different.');
      return;
    }

    dbCommand( "UPDATE Persons SET subId=subId+1 WHERE id='$chosenId'" );
    dbCommand( "INSERT INTO Persons(id, subId, name, countryId, gender, year, month, day) VALUES( '$chosenId', '1', '$chosenName', '$chosenCountryId', '$chosenGender', '$chosenYear', '$chosenMonth', '$chosenDay')" );

    noticeBox( true, "Successfully updated $chosenNameHtml($chosenId).");
  }
}

#----------------------------------------------------------------------
function offerChoices () {
#----------------------------------------------------------------------
  global $chosenId, $chosenConfirm;

  adminHeadline( 'Change Person' );

  echo "<p style='width:45em'>Choose 'Fix' if you want to replace a person's information in the database. It will modify the Persons table accordingly and the Results table if the person's name is different. This should be used to fix mistakes in the database.</p>\n";

  echo "<p style='width:45em'>Choose 'Update' if the person's name or country has been changed. It will add a new entry in the Persons table and make it the current information for that person (subId=1) but it will not modify the Results table so previous results keep the old name.</p>\n";

  echo "<hr />";

  echo "<form method='POST' action='$_SERVER[PHP_SELF]'>\n";
  echo "<table class='prereg'>\n";
  textField( 'id', 'WCA Id', $chosenId, 11 );

  if( ! $chosenConfirm ) {
    echo "<tr><td>&nbsp;</td><td style='text-align:center'>";
    echo "<input type='submit' id='confirm' name='confirm' value='Confirm' style='background-color:#9F3;font-weight:bold' /> ";
    echo "</td></tr></table></form>";
    return;
  }

  $persons = dbQuery( "SELECT * FROM Persons WHERE id='$chosenId' AND subId=1" );
  if( count( $persons ) == 0 ){
    noticeBox(false, 'Unknown WCA Id '.$chosenId);
    return;
  }

  $person = array_shift( $persons );
  extract( $person );

  #--- Name
  textField( 'name', 'Name', $name, 50 );

  #--- Country
  $countries = dbQuery( "SELECT * FROM Countries" );
  foreach( $countries as $country ){
    $cId   = $country['id'  ];
    $cName = $country['name'];
    if( $cId == $countryId )
      $fieldC .= "  <option value=\"$cId\" selected='selected' >$cName</option>\n";
    else
      $fieldC .= "  <option value=\"$cId\">$cName</option>\n";
  }
  echo "  <tr><td width='30%'><label for='countryId'><b>Country</b></label></td>\n";
  echo "      <td><select id='countryId' name='countryId'>\n";
  echo $fieldC;
  echo "      </select></td></tr>\n";

  #--- Gender
  if( $gender == 'm' )
    $fieldG = "Male : <input type='radio' id='gender' name='gender' value='m' checked='checked' /> Female : <input type='radio' id='gender' name='gender' value='f' />";
  else if( $gender == 'f' )
    $fieldG = "Male : <input type='radio' id='gender' name='gender' value='m' /> Female : <input type='radio' id='gender' name='gender' value='f' checked='checked' />";
  else
    $fieldG = "Male : <input type='radio' id='gender' name='gender' value='m' /> Female : <input type='radio' id='gender' name='gender' value='f' />";
  echo "  <tr><td width='30%'><label for='gender'><b>Gender</b></label></td><td>$fieldG</td></tr>\n";

  #--- DoB
  echo "  <tr><td><b>Date of birth</b></td><td>";
  echo numberSelect( "day", "Day", 1, 31, $day );
  echo numberSelect( "month", "Month", 1, 12, $month );
  echo numberSelect( "year", "Year", date("Y"), date("Y")-100, $year );
  echo "</td></tr>\n";  

  #--- Submit
  echo "<tr><td>&nbsp;</td><td style='text-align:center'>";
  echo "<input type='submit' id='update' name='update' value='Update' style='background-color:#9F3;font-weight:bold' /> ";
  echo "<input type='submit' id='fix' name='fix' value='Fix' style='background-color:#9F3;font-weight:bold' /> ";
  echo "</td></tr></table></form>";

}

#----------------------------------------------------------------------
function textField ( $id, $label, $default, $size ) {
#----------------------------------------------------------------------
  echo "  <tr><td width='30%'><label for='$id'><b>$label</b></label></td><td><input id='$id' name='$id' type='text' value='$default' size='$size' /></td></tr>\n";
}

#----------------------------------------------------------------------
function numberSelect ( $id, $label, $from, $to, $default ) {
#----------------------------------------------------------------------

  $result = "<select id='$id' name='$id' style='width:5em'>\n";
  foreach( range( $from, $to ) as $i ){
    if( $i == $default )
      $result .= "<option value='$i' selected='selected'>$i</option>\n";
    else
      $result .= "<option value='$i'>$i</option>\n";
  }
  $result .= "</select>\n\n";
  return "<label for='$id'>$label:</label> $result";  
}

?>
