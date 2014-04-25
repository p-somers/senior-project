<!doctype html>
<html>
  <head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
    <script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css" />
    <script type="text/javascript" src="js/main.js"></script>
    <script>
      $( document ).ready( function () {
        init();
      } );
    </script>
    <link rel="stylesheet" type="text/css" href="css/main.css" />
    <title>Home</title>
  </head>
  <body>
    <div id="logo"></div>
    <div id="menu">
      <div id="about_button"  class="menu_item">About</div>
      <div id="main_button"   class="menu_item">Main</div>
      <div id="signup_button" class="menu_item">Sign up</div>
    </div>
    <div style="clear:left;"></div>
    <!--div id="about" class="content">Psychohistory... just google it.</div-->
    <div id="main" class="content">
      <div id="newsfeed">News feed goes here, displaying "predicted" news or current news.</div>
      <div id="right_half">
        <form name="indicators" id="indicators">Select tracked indicators to graph:<br>
          <div style="float:left">
            <?php
              //this automatically figures out the indicators based on what's in the database.
              require_once './charts/db.php';
              $db = db_conn::getDB();
              $STH = $db->query( 'desc data' );
              $STH->setFetchMode( PDO::FETCH_ASSOC );  
              $i = 1;
              while( $row = $STH->fetch() ) {  
                if( $i++ > 3 ) { //To miss 'id', 'dstamp', and 'words'
                  $title = $row[ 'Field' ];
                  if( strlen( $title ) > 0 ) {
                    echo '<input type="checkbox" name="indicator" value="'.$title.'" checked="checked">';
                    echo str_replace("_", " ", $title ).'<br>';//Just to make indicators with "_" look pretty
                  }
                }
                if( ( $i - 1 ) % 3 == 0 ) { // - 1 because the first column should only have two.
                  echo '</div><div style="float:left">';
                }
              }
            ?>
          </div>
          <br style="clear:both;"/>
        </form>
        <div id="graph_area">
          <form name="dates" id="dates">
            <div class="date">From: <input type="text" name="date" id="from" /></div>
            <div class="date">To: <input type="text" name="date" id="to" /></div>
            <div id="graph_button">Graph</div>
          </form>
          <div id="graph">
          </div>
        </div>
      </div>
    </div>
    
  </body>
</html>