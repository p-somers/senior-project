<?php
  /* Author: Peter Somers
   */
  require_once 'db.php';
  $start_date = filter_input( INPUT_GET, 'start_date' );
  $end_date   = filter_input( INPUT_GET, 'end_date' );
  $indicators = json_decode( filter_input( INPUT_GET, 'indicators' ) );
  //echo '+'.count( $indicators );
  $indicator_array = array_fill( 0, count( $indicators ), array() );
  
  $query = "SELECT * FROM data WHERE dstamp >= STR_TO_DATE( ':start_date', '%m/%d/%Y %T' )
                                 AND dstamp <= STR_TO_DATE( ':end_date',   '%m/%d/%Y %T' )";
   
  //Updated to use a prepared statement
  $db = db_conn::getDB();
  $stmt = $db->prepare( $query );
  $stmt->bindValue(':start_date',$start_date);
  $stmt->bindValue(':end_date',$end_date);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  if($rows){
    foreach ( $STH as $row ) {
      for( $i = 0; $i < count( $indicators ); $i++ )
      {
        $val = $row[ $indicators[ $i ] ];
        if( strlen( $val ) > 0 )
          array_push( $indicator_array[ $i ], $val );
      }
    
    }
  }
  echo json_encode( $indicator_array );
  die();
?>