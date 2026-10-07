#!perl.exe
use strict;     # make sire everything must be declared
use warnings;   #complain about mall stuff
use CGI qw(:standard);   # use the Common Gateway Interface to talk via the web server
print header; # this tells the web server iots good data.  It prints the http headers, which is basically a 200 OK\n\n

# everything above is standard to talk to web clients

print 'Hello World!';   # and this is the ismplest program. The above is crufty for web use and security


