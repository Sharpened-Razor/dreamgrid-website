#!perl.exe

# stats set for LSLEditor Island
# https://127.0.0.1:80/stats/Server.plx?Region=LSLEditor%20Island&Name=Ferd%20Frederix&X=100&Y=100

BEGIN {

    chdir '/Inetpub/secondlife/cgi';
    use lib qw(lib .);
    my $env = $ENV{REMOTE_ADDR};
    use CGI qw(:standard);
    if ( ($env eq '' || $env ne ($ENV{SERVER_ADDR} // '')) ) {
        print header;

        #exit;
    }

}

$| = 1;

# put any avatar names here you want to exclude
my @exclude = ();
use strict;
use warnings;
use feature ':5.10';

use MYSQL;

use URI::Escape;
use Util;
my $schema = Util::mysql_connect;
$schema->storage->debug(0);

my $req   = CGI->new();
my $debug = 1;

my $sim        = $req->param('Region');
my $location   = $req->header('X-SecondLife-Region') || '';
my $Name       = $req->param('Name') || '';
my $RegionSize = $req->param('RegionSize') || '';
my $X          = $req->param('X') || 0;
my $Y          = $req->param('Y') || 0;

if ($debug) {

    $Name       = 'Ferd Frederix';
    $sim        = 'Welcome';
    $RegionSize = '<256.000000, 256.000000, 0.000000>';
    $X          = '129.364487';
    $Y          = '130.629395';
}

$RegionSize =~ /<(\d+)?\./;
$RegionSize = $1;

my $fail = grep { $Name =~ /$_/ } @exclude;
if ( !$fail ) {
    print "\n$Name visited $sim\n";

    my $rs = $schema->resultset('Sim')->update_or_create(
        {

            regionname => $sim,
            regionsize => $RegionSize,
            locationX  => $X,
            locationY  => $Y,
        }
    );

    my $i = $schema->resultset('Visitor')->new(
        {
            name       => $Name,
            locationX  => $X,
            locationY  => $Y,
            regionname => $sim
        }
    )->insert;
    if ( !$i ) {
        ErrorExit();
    }

    print "Inserted $Name\n";
}

print("Sim Stats Online");

sub ErrorExit {
    print("Stats offline");
    exit;
}
