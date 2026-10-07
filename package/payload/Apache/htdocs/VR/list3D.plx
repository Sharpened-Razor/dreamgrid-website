#!perl.exe
use 5.010;
use strict;
use warnings;
use CGI qw(:standard);   # use the Common Gateway Interface to talk via the web server
print header; # this tells the web server it is good data.  It prints the http headers, which is basically a 200 OK\n\n

use IO::All;

my $io = io('photo');                  # Create new directory object
my @contents = $io->all;
my @output;
foreach my $fname (@contents)
{
    my $part = $fname->name;
    $part =~ s/.*\\//;
    $part =~ s/\.jpg//;
    
    push @output, $part;
    if ( scalar @output > 5) # max is 6
	{
		last;
	}
}
    
@output = sort @output;

use JSON;
my $result = to_json(\@output);
print $result;
