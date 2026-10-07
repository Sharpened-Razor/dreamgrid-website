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
use Template;
my $tt = Template->new({
       INTERPOLATE  => 0,
   }) || die "$Template::ERROR\n";

# Select all records from the UserAccounts table into a recordset
my $userRS =  $schema->resultset('Useraccount')->search({},{order_by => { -asc => [qw/lastname firstname/] }});

my $count = $userRS->count;   # OO method for how many users on my grid


my @data ;
foreach my $contact ($userRS->all) #  read each row object into $contact
{
   push @data, {  email => $contact->email,     # read each column and store it in a hash by ID
                  first => $contact->firstname,
                  last  => $contact->lastname,

               };
}

$tt->process('template/users.tt',  {
      users=>\@data,
      usercount=>$count,

   } ) || die $tt->error(), "\n"; # convert to a web page

