#!perl.exe
use strict;
use warnings;
use lib qw(lib); # look in lib for modules and databases
binmode (STDOUT,':utf8'); # we can use unicode
use CGI qw(:standard); # so we can read and write to the web
my $Input = CGI->new(); # read data from the web


# print the first 100 assets

print $Input->header(   # and print it as UTF-8
      -type    => 'text/html',
      -charset =>  'utf-8',
   );




# Dbix::Class stuff - set up the DSN in your ODBC driver, and put the details of the DSN in DSN.txt
use Robust::Util; 
my $schema = Robust::Util::mysql_connect;
$schema->storage->debug(0);   # set to 1 to see detailed database SQL as it is generated


# Select all records from the UserAccounts table into a recordset
my $RS =  $schema->resultset('Asset');



### OO interface:
#use Crypt::Digest::SHA256;
#
#open  (FILE, "<", "";)
#
#$d = Crypt::Digest::SHA256->new;
#
#$d->addfile(FILE);
#$result_raw  = $d->digest;     # raw bytes
#$result_hex  = $d->hexdigest;  # hexadecimal form
#$result_b64  = $d->b64digest;  # Base64 form
#$result_b64u = $d->b64udigest; # Base64 URL Safe form


print "<table>";

my $counter = 1;
foreach my $r ($RS->all) #  read each row object as $r 
{
   print "<tr><td>" . $r->name . "</td><td>" .$r->id. "</td></tr>";
   
   
   if ($counter++ > 100) {
      
      print qq!</table>!;
      
      exit;
   }
}




