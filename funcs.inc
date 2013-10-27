<?php
#
# /www/q/funcs.inc
#
##########################

function logit($msg) {
  global $logit_to_stdout;
  $msg=rtrim($msg);
  syslog(LOG_INFO, "$msg");

  # Write a message on stdout too?
  if ($logit_to_stdout) {
    echo "logit: $msg";
  }
}

function debug1($msg) {
  global $debug_flag1, $debug_to_syslog;
  $msg=rtrim($msg);
  if (($debug_flag1==TRUE) && (strlen($msg)>0) ) {
    if ($debug_to_syslog===TRUE) {
      syslog(LOG_INFO, "Debug1: $msg");
    } else {
      echo "Debug1: $msg\n<br>";
    }
  }
}

function debug2($msg) {
  global $debug_flag2, $debug_to_syslog;
  $msg=rtrim($msg);
  if (($debug_flag2==TRUE) && (strlen($msg)>0) ) {
    if ($debug_to_syslog) {
      syslog(LOG_INFO, "Debug2: $msg");
    } else {
      echo "Debug2: $msg\n";
    }
  }
}


function query2excel_notitle($query, $linkID, $worksheet, $format0, $format1, $excelrow)
{
  $debug=0;
  $debug && syslog(LOG_INFO, "funcs.inc: $query");

  $res = mysql_query($query, $linkID);
  if (!$res) { die('Invalid query: ' . mysql_error()); }

  # Field titles
  $cols = mysql_num_fields($res);
  $rowcount=mysql_num_rows($res);

  while ($row = mysql_fetch_row($res)) {
    for ($col = 0; $col < $cols; $col++){
      $worksheet->write($excelrow,$col,$row[$col], $format0);
      #echo "$row[$col] ";
    }
    #echo "\n ";
    $excelrow++;
  }
  return($excelrow);
}

//
// Execute query and return assoc array
function query2excel($query, $linkID, $worksheet, $format0, $format1, $excelrow)
{
  $debug=0;
  $debug && syslog(LOG_INFO, "funcs.inc: $query");

  $res = mysql_query($query, $linkID);
  if (!$res) { die('Invalid query: ' . mysql_error()); }

  # Field titles
  $cols = mysql_num_fields($res);
  $rowcount=mysql_num_rows($res);
  #echo "rowcount $rowcount ";

  # Print titles
  for ($col = 0; $col < $cols; $col++){
    $worksheet->write($excelrow,$col,mysql_field_name($res, $col), $format1);
    #echo "$row[$col] ";
  }
  #echo "\n ";
  $excelrow++;

  while ($row = mysql_fetch_row($res)) {
    for ($col = 0; $col < $cols; $col++){
      $worksheet->write($excelrow,$col,$row[$col], $format0);
      #echo "$row[$col] ";
    }
    #echo "\n ";
    $excelrow++;
  }
  return($excelrow);
}

## SQL and expect just one /field/row to return
function v_sql_1_select($query) {
  #logit($query);
  #global $connect;
  $result=NULL;
  $res = mysql_query($query);
  if (!$res) {
    logit('Invalid query: ' . mysql_error());

  } else if (mysql_num_rows($res)==1) {
    list($result)=mysql_fetch_array($res, MYSQL_NUM);
  }
  return($result);
}


//
// Execute query and return assoc array
//   Assuming a table t1 with 2 Fields Code and Value:
//   $r= mysql_fetch_all("SELECT * from t1")
//   foreach ($r as $row) { echo "$row[Code], $row[Value]\n";
//
function mysql_fetch_all($query){
  debug2('mysql_fetch_all: ' . $query);

  unset($r);
  $r=@mysql_query($query);
  if(!$r) {
    logit('mysql_fetch_all error:' . mysql_error());
    return mysql_errno();   # return the mysql err num
  }

  debug2('mysql_fetch_all rows: ' . @mysql_num_rows($r) .', errno=' . @mysql_errno($r) .', err=' .@mysql_error($r));

  if(@mysql_num_rows($r)) {
    while($row=mysql_fetch_array($r)) {
      $result[]=$row;     #return a row
    }
  } else {
    ## no data so return an integer, 0
    $result=@mysql_num_rows($r);
  }
  return $result;
}



function mysql_fetch_one($query){
  #echo "QUERY: $query\n";
  $r=@mysql_query($query);
  if($err=mysql_errno())return $err;
  if(@mysql_num_rows($r))
  return mysql_fetch_array($r);
}

?>
