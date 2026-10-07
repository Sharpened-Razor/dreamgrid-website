#!perl.exe


#use strict;
use warnings;
use lib qw(. lib); # look in lib for modules and databases
binmode (STDOUT,':utf8'); # we can use unicode
use CGI qw(:standard); # so we can read and write to the web
my $Input = CGI->new();

print $Input->header(   # and print it as UTF-8
      -type    => 'text/html',
      -charset =>  'utf-8',
   );

# Dbix::Class stuff - set up the DSN in your ODBC driver, and put the details of the DSN in DSN.txt
use Robust::Util; 
my $schema = Robust::Util::mysql_connect;
$schema->storage->debug(0);   # set to 1 to see detailed database SQL as it is generated

# See the fabulous Template::Toolkit at http://template-toolkit.org/
use Template;
my $tt = Template->new({
       INTERPOLATE  => 0,
   }) || die "$Template::ERROR\n";


my $welcomenote =  $schema->resultset('Asset')->search({name=>'WelcomeNote'})->first->data;


my @data ;

$tt->process('template/users.tt',  {
      welcome => $welcomenote,
   } ) || die $tt->error(), "\n"; # convert to a web page

