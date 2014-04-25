<html>
  <style type="text/css">
    div.debug {
      width : 200px;
      float : right;
    }
  </style>
</html>
<?php
  //#! /usr/bin/php
  /**
  * Make sure SimplePie is included. You may need to change this
  * to match the location of autoloader.php
  * For 1.0-1.2:
  * require_once('../simplepie.inc');
  * For 1.3+:
  * (I have no idea... -Peter)
  * Strategy: get a bunch of arrays, then loop through them all at once (?)
  * Each array stores info from new to old.
  * Currently, I've left "foreach" loops for testing the arrays in, but
  * but they're commented out.
  */
require_once('../php/autoloader.php');
require_once('../php/tagspider.php');
require_once('../db.php');
require_once('Date.php');
date_default_timezone_set("America/Los_Angeles");//Necessary for DateTime
class historical_script {
  function run() {
    
    //Indicators
    $dow_values;
    $nasdaq_values;
    $oil_values;
    $approval_values;
    $disapproval_values;

    //--------------------------------------------------------------------------
    //Dow info. The CSV file used is downloaded from 
    //"http://research.stlouisfed.org/fred2/series/DJIA/downloaddata"
    //Unfortunately it has to be downloaded automatically.
    //Make sure it's set to "CSV" in the "File Format" field.
    $filename = './DJIA.csv';
    $num_lines =  count(file($filename));
    $dow_values[ $num_lines - 1 ][ 2 ];//num_lines - 1 because of header info
  
    if(file_exists($filename)) {
      $file = fopen($filename, 'r');

      if($file != FALSE) {
        $i = 0;
        while((($info = fgetcsv($file, 1000, ",")) !== FALSE) && ($i < $num_lines)) {
          //echo 'Date: '.$info[ 0 ].'<br>';
          if($i != 0)//This skips the header
            $dow_values[ $info[ 0 ] ] = $info[ 1 ];
            $i++;
          }
        fclose($file);
      }
    }
    else
      echo 'Error downloading info';
    //--------------------------------------------------------------------------

    //--------------------------------------------------------------------------
    /*Getting the stock info*/
    //$url = "http://www.google.com/finance/historical?q=NASDAQ:NDAQ&output=csv";
    $baseurl = "http://www.google.com/finance/historical?";
    $extra   = "cid=682852&startdate=Jul+2%2C+2002&enddate=Mar+7%2C+2013&num=30&ei=ewC0UMCgCuj40gGr7QE"; 
    $format  = "&output=csv";
    $url = $baseurl . $extra . $format;
    $num_lines = count(file($url));
    $nasdaq_values[ $num_lines - 1 ][ 6 ];
    $file = fopen($url, "r"); 
    if($file != FALSE) {
      $i = 0;
      while((($info = fgetcsv($file, 1000, ",")) !== FALSE) && ($i < $num_lines)) {
        if($i != 0)//skips the header
          $nasdaq_values[ date('Y-m-d', strtotime($info[ 0 ])) ] = $info[ 4 ];
        $i++;
      }
      $nasdaq_values = array_reverse($nasdaq_values);
      //print_r($nasdaq_values);
      fclose($file);
    }
    else
      echo 'Error downloading info';
   
    //--------------------------------------------------------------------------
    /*
     * Oil info
     */
    $url = 'http://www.opec.org/basket/basketDayArchives.xml';
    $oil_prices = simplexml_load_file($url);

    $i = 0;
    $num_lines = count($oil_prices);
    foreach($oil_prices -> children() as $child) {
      $date = date('Y-m-d', strtotime($child[ 'data' ]));
      $oil_values[ $date ] = floatval($child[ 'val' ]);
      $i++;
    }
    //print_r($oil_values);
    //--------------------------------------------------------------------------
   
    //--------------------------------------------------------------------------
    //Getting presidential approval ratings.
    //The url will need to be changed when new presidents get elected.
    /**
     * This is going to be tricky. Not every day is accounted for directly.
     * We'll have to check for skipped dates.
     */
    //First Obama...
    $obama_url  = "http://www.gallup.com/viz/v1/csv/8386b935-9a6b-4a07-ae74-";
    $obama_url .= "78e02ac52871/POLLFLEXCHARTVIZ/OBAMAJOBAPPR113980.aspx";
    //Now Bush:  
    $bush_url  = "http://www.gallup.com/viz/v1/csv/8386b935-9a6b-4a07-ae74-";
    $bush_url .= "78e02ac52871/POLLFLEXCHARTVIZ/BUSHJOBAPPR111769.aspx";
    $obama_appr_length = count(file($obama_url));
    $bush_appr_length  = count(file($bush_url));
    $num_lines  = $obama_appr_length + $bush_appr_length - 14;
    //The "- 14" accounts for header/footer stuff
    $values;
    $file = fopen($bush_url, 'r');
    if($file != FALSE) {
      $i = 0;
      $index = 0;
      while(($info = fgetcsv($file, 1000, ",")) != FALSE) {
        if($i > 4 && $i < ($bush_appr_length - 3)) {
          $date = historical_script::get_date('middle', $info[ 0 ]);
          $approval_values[ $date->format('Y-m-d') ] = doubleval($info[ 1 ]);
          $disapproval_values[ $date->format('Y-m-d') ] = doubleval($info[ 2 ]);
        }
        $i++;
        $index++;
      } 
      fclose($file);
    }
    else
      echo 'Error downloading Bush approval ratings';

    $file = fopen($obama_url, 'r');
    if($file != FALSE) {
      $i = 0;
      while(($info = fgetcsv($file, 1000, ',')) != FALSE) {
        if($i > 4 && $i < ($obama_appr_length - 3)) {
          $date = historical_script::get_date('middle', $info[ 0 ]);
          $approval_values[ $date->format('Y-m-d') ] = doubleval($info[ 1 ]);
          $disapproval_values[ $date->format('Y-m-d') ] = doubleval($info[ 2 ]);
      }
      $i++;
      $index++;
      } 
      fclose($file);
    }
    else
      echo 'Error downloading Obama approval ratings';
    
    historical_script::fill_gaps($dow_values);
    historical_script::fill_gaps($nasdaq_values);
    historical_script::fill_gaps($oil_values);
    historical_script::fill_gaps($disapproval_values);
    historical_script::fill_gaps($approval_values);
    
    $date = new DateTime('2006-10-21');
    $now  = new DateTime();
    
    $db = db_conn::getDB();
    while($date < $now) {
      $date_str = $date->format('Y-m-d');
      $next_day = strtotime('+1 day', strtotime($date_str));
      $dow         = $dow_values[ $date_str ];
      $nasdaq      = $nasdaq_values[ $date_str ];
      $gas         = $oil_values[ $date_str ];
      $approval    = $approval_values[ $date_str ];
      $disapproval = $disapproval_values[ $date_str ];
      $query = "UPDATE data SET dow = '$dow', nasdaq = '$nasdaq', gas = '$gas',
                                president_approval = '$approval',
                                president_disapproval = '$disapproval'
                                WHERE dstamp = STR_TO_DATE('$date_str', '%Y-%m-%d')";
  
      $STH = $db->query($query); 
      if(!$STH) {
        $error = PDO::errorInfo();
        echo 'Error connecting to database for date '.$date_str.': '.$error[ 2 ].'<br>';
      }
      else
        echo 'Updated '.$date->format('Y-m-d').'<br>';
      $date = new DateTime(date("Y-m-d", $next_day));  
    }
  }
  
  /**
   * Some of our sources use date ranges, so here we can get the first,
   * second, and third dates from a string range.
   * Note: This also works with something like '4/12/2014-4/15', where
   * they aren't all full dates.
   */
  function get_date($which, $str) {
    $dates = explode('-', $str);
   
    if($which === 'first') {
      if(substr_count($dates[ 0 ], '/') === 2)
        return new DateTime(date("Y-m-d", strtotime($dates[ 0 ])));
      else {
        $year = substr($dates[ 1 ], strrpos($dates[ 1 ], '/', -4) + 1);
        return new DateTime(date("Y-m-d", strtotime($dates[ 0 ].'/'.$year)));
      }
    }
    else if($which === 'second' || $which === 'last') { //because I know I will forget
      if(substr_count($dates[ 1 ], '/') === 2)
        return new DateTime(date("Y-m-d", strtotime($dates[ 1 ])));
      else {
        $month = substr($dates[ 0 ], 0, strrpos($dates[ 0 ], '/'));
        return new DateTime(date("Y-m-d", strtotime($month.'/'.$dates[ 1 ])));
      }
    }
    else if($which === 'middle') {
      $first  = historical_script::get_date('first', $str);
      $second = historical_script::get_date('second', $str);
      $interval = $first->diff($second);
      $days = $interval->days / 2.0;
      $increment = '+'.$days.' day';
      if($days > 1)
        $increment .= 's';
      $date = date("Y-m-d", strtotime($increment, strtotime($first->format('Y-m-d'))));
      return new DateTime($date);
    }
  }
  
  function print_array($array) {//because I don't like php's built-in funtion
    foreach($array as $key => $val) {
      echo 'Key: '.$key.' value: '.$val.'<br>';
    }
  }
  
  /**
   * This goes through an array that uses dates as keys. It finds any gaps and
   * fills them in with the data from the last valid day.
   */
  function fill_gaps(&$array) {
    reset($array);
    $first =  key($array);
    $prev_date = date("Y-m-d", strtotime('-1 day', strtotime($first)));
    foreach($array as $date => $val) {
      $prev_date = date("Y-m-d", strtotime('+1 day', strtotime($prev_date)));
      while(new DateTime($prev_date) < new DateTime($date)) {
        $array[ $prev_date ] = $val;
        $prev_date = date("Y-m-d", strtotime('+1 day', strtotime($prev_date)));
      }
    }
  }
}

$script = new historical_script();
$script->run();
?>