
/* function get_cookie ( cookie_name )
{
  var results = document.cookie.match ( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );
 
  if ( results )
    return ( unescape ( results[2] ) );
  else
    return null;
}
if ( ! get_cookie ( "Delyvery" ) )
{
  document.getElementById("cuca").innerHTML = "390";
}
else
{
  document.getElementById("cuca").innerHTML = "190";
}
 */



/* function get_cookie (Delyvery)
{
  var results = document.cookie.match ( '(^|;) ?' + Delyvery + '=([^;]*)(;|$)' );
 
  if ( results )
    return ( unescape ( results[2] ) );
  else
    return null;
}

if ( ! get_cookie ( "Delyvery" ) )
{
  var Delyvery = "190";
 
  if ( Delyvery )
  {
    var current_date = new Date;
    var cookie_year = current_date.getFullYear ( );
    var cookie_month = current_date.getMonth ( );
    var cookie_day = current_date.getDate ( ) + 30;
    set_cookie ( "username", username, cookie_year, cookie_month, cookie_day );    
  }
  document.getElementById("cuca").innerHTML = Delyvery;
}
else
{
  document.getElementById("cuca").innerHTML = "190";
}
test(); */


// Отключил на время теста


$(function(){
  setTimeout(function(){
  
  delivery_190();

  function get_cookie (Delyvery)
  {
    var results = document.cookie.match ( '(^|;) ?' + Delyvery + '=([^;]*)(;|$)' );
 
    if ( results )
      return ( unescape ( results[2] ) );
    else
      return null;
  }

  function delivery_190 () {
    var Delyvery = "390";
    if ( !get_cookie("Delyvery")) {
          
      if ( Delyvery ) {
        var current_date = new Date;
        var cookie_year = current_date.getFullYear ( );
        var cookie_month = current_date.getMonth ( );
        var cookie_day = current_date.getDate ( ) + 30;
        set_cookie ( "Delyvery", Delyvery, cookie_year, cookie_month, cookie_day );    
      }
      document.getElementById("cuca").innerHTML = Delyvery;
    } else {
      
      if (Delyvery !== "390") {
        Delyvery = "390";
      }
      
      /* if ( Delyvery ) {
        var current_date = new Date;
        var cookie_year = current_date.getFullYear ( );
        var cookie_month = current_date.getMonth ( );
        var cookie_day = current_date.getDate ( ) + 30;
        set_cookie ( "Delyvery", Delyvery, cookie_year, cookie_month, cookie_day );    
      }  */


      document.getElementById("cuca").innerHTML = Delyvery;
    }
  }

  function set_cookie ( name, value, exp_y, exp_m, exp_d, path, domain, secure )
  {
    var cookie_string = name + "=" + escape ( value );
  
    if ( exp_y )
    {
      var expires = new Date ( exp_y, exp_m, exp_d );
      cookie_string += "; expires=" + expires.toGMTString();
    }
  
    if ( path )
          cookie_string += "; path=" + escape ( path );
  
    if ( domain )
          cookie_string += "; domain=" + escape ( domain );
    
    if ( secure )
          cookie_string += "; secure";
    
    document.cookie = cookie_string;
  }

  }, 2000); 

});