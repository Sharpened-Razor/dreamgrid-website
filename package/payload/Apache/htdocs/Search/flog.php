<?php
/***************************************************************
 *
 *  Helpful FILE Logger function to track and trace what is happening
 *  in your PHP Program.
 *
 *  You can call this function with a syntax similar to printf if you wish
 *
 *  You can define these constant to *true* to controll the behaviour
 *  of the function.
 *
 *  Examples of Valid calls
 *  
 *  flog();  //prints a simple "trace" in the log so you know this was called
 *  flog( $obj );
 *  flog( "this is a string" );
 *  flog( $array );
 *  flog( "This is a number: %6d", 10 );
 *
 *  FLOG_IGNORE        - suppresses FLOG for when you get online!
 *
 *  FLOG_SHOW_TIME     - show the time of the log
 *  FLOG_SHOW_STACK    - print the stack trace
 *  FLOG_CLEAR_LOG     - clear the logs when including this file
 *  FLOG_STRIP_PATH    - set this to Base path that you want removed
 *                       when logging. Makes logs more readable
 *  FLOG_FILE_NAME     - log file name - defaults to "flog.log"
 *  FLOG_ONLINE_SERVER - if you set this to the name of your online server
 *                       flog will stop working online.
 * 
 *
 *  Author: Ilie Pandia
 *  Email:  iliep@softconsultant.ro
 *  Date:   26-Nov-2011
 */

define ("FLOG_IGNORE", true);	// comment  to get data in logs

define ("FLOG_SHOW_TIME",true);

if( defined( "FLOG_HAS_BEEN_INCLUDED" ) ) return;
define( "FLOG_HAS_BEEN_INCLUDED", true );

if( !defined( "FLOG_ONLINE_SERVER" ) )
	define( "FLOG_ONLINE_SERVER", "softconsultant.ro" );

//setup a default strip path
if( !defined( "FLOG_STRIP_PATH" ) ){
	$webroot = str_replace( "\\", "/", $_SERVER[ 'DOCUMENT_ROOT' ] );
	define( "FLOG_STRIP_PATH", $webroot );
}
	
//by default do not clear the log file
if( !defined( "FLOG_CLEAR_LOG" ) )
	define( "FLOG_CLEAR_LOG", false );
	
//by default show the time in the logs
if( !defined( "FLOG_SHOW_TIME" ) )
	define( "FLOG_SHOW_TIME", true );
	
//by default do NOT print the stack in the logs
if( !defined( "FLOG_SHOW_STACK" ) )
	define( "FLOG_SHOW_STACK", false );
	
//by default logs are ON
if( !defined( "FLOG_IGNORE" ) )
	define( "FLOG_IGNORE", false );
	
//default log file name
if( !defined( "FLOG_FILE_NAME" ) )
	define( "FLOG_FILE_NAME", "flog.log" );
	
//clear the log file if so instructed
if( FLOG_CLEAR_LOG ){
	$__log_file = dirname( __FILE__ ) . "/" . FLOG_FILE_NAME;
	if( file_exists( $__log_file )){
		unlink( $__log_file );
		touch( $__log_file );
	}
}
		
function flog( $mixed = null ){
	//check if disabled
	if( FLOG_IGNORE ) return;
	
	//check if online and if so disable flog
	//if( $_SERVER['SERVER_NAME'] == FLOG_ONLINE_SERVER )
	//	return;
	
	//call sprintf to pretty print the message
	if( is_string( $mixed ) && ( func_num_args() > 1 ) ){
		$args = func_get_args();
		$mixed = call_user_func_array( 'sprintf', $args );
	}
	
	//if no message was given add this thing
	if( empty( $mixed ) ) $mixed = "- - - - - - - -";
	
	//pretty print the message
	$message = print_r( $mixed, true );
	
	//get the calling frame
	$stack = debug_backtrace();
	$class    = "";
	if( !empty( $stack[0]['class'] ) )
		$class  = $stack[0]['class']."::";
	$function = "";
	if( !empty( $stack[1]['function'] ) )
		$function = $stack[1]['function']; //caller function
		
	$line     = $stack[0]['line'];
	$file     = $stack[0]['file'];

	//pretty print the stack
	$out = "";
	if( FLOG_SHOW_STACK ){
		$space = "";
		$i = 1;
		foreach( $stack as $frame ){
			$class = "";
			$function = "";
			if( !empty( $stack[$i]['class'] ) )
				$class = $stack[$i]['class'] . '::';
			if( !empty( $stack[$i]['function'] ) )
				$function = $stack[$i]['function'];
			$function = $class . $function;
			if( !empty( $function ) )
				$function = " - $function()";
			else
				$function = " - [main]";
			$out .= "~ {$space}{$frame['file']}:{$frame['line']}$function\n";
			$space .= "   ";
			$i++;
		}
	}
	$stack = $out;
	$stack = str_replace( "\\", "/", $stack );
	$stack = str_replace( FLOG_STRIP_PATH, "...", $stack );
	
	$file = str_replace( "\\", "/", $file );
	$file = str_replace( FLOG_STRIP_PATH, "...", $file );
	$line = sprintf( "%4d", $line );
	$where = "$file:$line";
	$time = date( 'Y-m-d H:i:s' );
	
	if( FLOG_SHOW_TIME )
		$prefix = "[$time]$where - ";
	else
		$prefix = "$where - ";
	//TODO: align prefixes to 5 chars
	
	$len = strlen( $prefix )-1;
	$ident = "."; while( $len ){ $ident .=" "; $len--; }
	$message = str_replace( "\n", "\n$ident", $message );
	
	$log_file = "../../../Logs/" . FLOG_FILE_NAME;
	
	error_log( $stack . $prefix . $message . "\n", 3, $log_file )
		or die( "Failed to write to the log file: $log_file! Permission problem?" );			
}
