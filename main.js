var current_page;

function init() {
  current_page = 1;
  $( '#main_button' ).css( 'background-color', '#D1E0FF' );
  $( 'div.menu_item' ).on( 'click', function( event ) {
    $( 'div.menu_item' ).css( 'background-color', '#FFFFFF' );
    event.target.style.backgroundColor = '#D1E0FF';//"#B2CCFF";
    $( '#menu' ).css( 'border', 'solid' );
    $( '#menu' ).css( 'border-radius', '15px' );
    
    //Getting the index of the targeted page
    var clicked = event.currentTarget;
    var buttons = document.getElementsByClassName( 'menu_item' );
    var target = 0;
    for( var i = 0; i < buttons.length; i++ )
      if( buttons[ i ].id === clicked.id )
        target = i;
    
    var pages = document.getElementsByClassName( 'content' );
    scrollTo( target, pages, 10, ( current_page - target ) * 2 );
    current_page = target;
  } );
  $( '#graph_button' ).mouseover( function( event ) {
    event.target.style.backgroundColor = '#D1E0FF';
  } ).mouseleave( function() {
    event.target.style.backgroundColor = '#FFFFFF';
  } ).mousedown( function() {
    event.target.style.backgroundColor = '#154890';
  } ).mouseup( function() {
    event.target.style.backgroundColor = '#D1E0FF';
  } ).on( 'click', function() {
    get_report();
  } );
  
  $( '#logo' ).width( $( '#logo_img' ).width() );
  $( '#logo' ).css( 'margin-left', 'auto' );
  $( '#logo' ).css( 'margin-right', 'auto' );
  var content_upper_margin = 25;
  var menu_height  = document.getElementById( 'menu' ).offsetHeight;
  var upper_height = document.getElementById( 'logo' ).offsetHeight;
     upper_height += document.getElementById( 'menu' ).offsetHeight + 25;
  var lower_height = window.innerHeight * 0.95 - ( upper_height + menu_height + content_upper_margin );
  
  document.getElementById( 'lower' ).style.marginTop = content_upper_margin;
  document.getElementById( 'upper' ).height = upper_height + 'px';
  $( '#lower' ).height( lower_height );
  $( '#lower' ).css( 'margin-top', '25px' );
  $( 'div.content' ).height( lower_height );
  setDatePickers();
  var pages = document.getElementsByClassName( 'content' );
  var content_width = 80;
  var page_margin = 5;
  $( 'div.content' ).width( ( content_width + '%' ) );
  pages[ 0 ].style.left = ( page_margin - content_width ) + '%';
  pages[ 1 ].style.left = ( ( 100 - content_width ) / 2 ) + '%';
  pages[ 2 ].style.left = ( 100 - page_margin ) + '%';
  console.log( pages[ 0 ].style.left );
}

function scrollTo( index, pages, location, move_amount ) {
  for( var i = 0; i < pages.length; i++ ) {
    var newval = parseInt( pages[ i ].style.left ) + move_amount;
    pages[ i ].style.left = newval + '%';
  }
  var target_loc = parseInt( pages[ index ].style.left );
  if( target_loc < location - Math.abs( move_amount ) || target_loc > location + Math.abs( move_amount ) )
    setTimeout( function(){ scrollTo( index, pages, location, move_amount ) }, 1 );
}

function setDatePickers() {//From Jesse's work, slightly edited by Peter
    jQuery( "#from" ).datepicker( {
      maxDate: -1,
      onClose: function( selectedDate ) {
        if( selectedDate.length > 0 )
          $( "#to" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    jQuery( "#to" ).datepicker( {
      maxDate: -1,
      onClose: function( selectedDate ) {
        if( selectedDate.length > 0 )
          $( "#from" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
  };

function get_report() {
  var arr = new Array( );
  for( var i = 0; i < document.indicators.indicator.length; i++ ) {
    if( document.indicators.indicator[ i ].checked )
      arr.push( indicators.indicator[ i ].value );
  }
  console.log( 'width: ' + $( '#graphs' ).width() );
  var post = {
    height     : $( '#graphs' ).height(),
    width      : $( '#graphs' ).width(),
    start_date : document.dates.date[ 'from' ].value,
    end_date   : document.dates.date[ 'to' ].value,
    indicators : JSON.stringify( arr )
  };
  $.ajax('./php/graphs.php', {
    async: false,
    type: 'get',
    data: post,
    //dataType: 'json',
    success: function( ret, status ) {
      if( status === 'success' ) {
        graph( ret, arr );
      }	
    },
    error: function (xhr, ajaxOptions, thrownError) {
      console.log(xhr.status);
      console.log(xhr.responseText );
      console.log(thrownError);
    }
  });
}

function graph( ret, arr ) {
  console.log( 'returned: ' + ret );
  
  var results = JSON.parse( ret );//[ arr[ 0 ] ].replace(/\&amp\;/gi, "&");
  //console.log( 'results: ' + results );
  //$( '#graphs' ).html( 'hi'+'<img src="' + results + '">' );
  $( '#graphs' ).html( '' );
  var graphs = '<ul>';
  for( var i = 0; i < arr.length; i++ )
  graphs = graphs + '<li><a href="#tabs-'+(i+1)+'">'+arr[i]+'</a></li>';
  graphs = graphs + '</ul>'; 
  for( var i = 0; i < 1; i++ )
    //$( '#graphs' ).html( '<img src="' + results[ arr[ i ] ] + '">' );
    graphs = graphs + '<div id="#tabs-'+(i+1)+'"><img src="'+results[arr[i]].replace(/\&amp\;/gi, "&")+'"></div>';
  $( '#graphs' ).html( graphs );
  $( '#graphs' ).tabs();
}
