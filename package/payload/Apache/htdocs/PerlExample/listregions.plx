#!perl.exe
use strict;
use warnings;
use lib qw(lib); # look in lib for modules and databases
binmode (STDOUT,':utf8'); # we can use unicode
use CGI qw(:standard); # so we can read and write to the web
my $Input = CGI->new(); # read data from the web

print $Input->header(   # and print it as UTF-8
      -type    => 'text/html',
      -charset =>  'utf-8',
   );

# this is so yoiudo not leak information to the Internet
my $env = $ENV{REMOTE_ADDR} // ''; 
if ( ($env eq '' || $env ne ($ENV{SERVER_ADDR} // '')) ) {
  print "This demo requires a local request.";
  exit;
}


# Dbix::Class stuff - set up the DSN in your ODBC driver, and put the details of the DSN in DSN.txt
use Robust::Util; 
my $schema = Robust::Util::mysql_connect;
$schema->storage->debug(0);   # set to 1 to see detailed database SQL as it is generated

# See the fabulous Template::Toolkit at http://template-toolkit.org/
use Template ;
my $tt = Template->new({
       INTERPOLATE  => 0,
   }) || die "$Template::ERROR\n";

# Select all records from the UserAccounts table into a recordset
my $RS =  $schema->resultset('Region');


my @data ;
foreach my $r ($RS->all) #  read each row object into $contact
{
   push @data, {  regionName => $r->regionname,     # read each column and store it in a hash by ID
                  serverPort => $r->serverhttpport,
                  serverPort => $r->serverhttpport,
                  sizex      => $r->sizex,
                                                  
               };
}


$tt->process('template/regions.tt',  {
      regions=>\@data,
      regioncount=>$RS->count,      
   
   } ) || die $tt->error(), "\n"; # convert to a web page

