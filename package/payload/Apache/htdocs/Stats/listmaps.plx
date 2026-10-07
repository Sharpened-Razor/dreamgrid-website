#!perl.exe
use strict;     # make sire everything must be declared
use warnings;   #complain about mall stuff
use CGI qw(:standard);   # use the Common Gateway Interface to talk via the web server

# author - Fred Beckhusen
# Copyright Outworldz, LLC.
# AGPL3.0  https://opensource.org/licenses/AGPL

my $Input = CGI->new();  # so we can read varaiables over the web and print things 
$| = 1;     # optional - this turns off buffered I.O.

my $debug;    # program scope = set to non-zero values for debug info
$debug = 1 if ( !$ENV{REMOTE_ADDR} );    # so we can single step in Komodo

#DBIx
use lib qw(lib .);    # This is where the DBI objecta are.
use Util;             # the fdatabase interface
my $schema = Util::mysql_connect;   # get a connection to Mysdql
$schema->storage->debug(0);         # of set to a 1 this wil print a lot of debug - specically yhe SQWL that it writes.

use Template;   # Template Toolkit to render web pages

my $tt = Template->new(
    {
        ABSOLUTE => 1,
    }
) || die "$Template::ERROR\n";

print $Input->header(   # and print it as UTF-8
      -type    => 'text/html',
      -charset =>  'utf-8',
   );

# read stuff off the config ini file
use Config::IniFiles;
use File::BOM;    # fixes a bug in Perl with UTF-8

# get the path to the Settings.ini
use Cwd;
my $path = getcwd();

use File::Basename qw(dirname);use File::Spec;
my $grid_root=Cwd::abs_path(getcwd());
while(!-f File::Spec->catfile($grid_root,'Settings.ini')){my $parent=dirname($grid_root);die "Grid settings not found" if $parent eq $grid_root;$grid_root=$parent;}
require File::Spec->catfile($grid_root,'Apache','htdocs','library','DreamGrid','Environment.pm');
my $file=File::Spec->catfile($grid_root,'Settings.ini');

# Read the Right Thing from a unicode file with BOM:
open( CONFIG, '<:via(File::BOM)', $file );
my $Config = Config::IniFiles->new( -file => *CONFIG );

if ( !$Config ) {
    print header;
    print "Cannot read INI";
    return;
}

if ($debug) {
    $ENV{REMOTE_ADDR} = '';
}

my @nothing;
my $port =  $Config->val( 'Data', 'ApachePort' ) || '';
my $public = $Config->val( 'Data', 'PublicVisitorMaps' ) || '';
if ( lc($public) ne 'true' ) {

    my $env = $ENV{REMOTE_ADDR} ;
    if ( !DreamGrid::Environment::local_request()) {
        my $dataout = "Access denied: a local request is required.";
        print $dataout;
        exit;
    }
}

###########################

my @sims;

my $rs = $schema->resultset('Sim')
  ->search( {}, { order_by => { -asc => 'regionname' } } );
my $width = '400';
my %unique;
foreach my $row ( $rs->all ) {

    my $X = $row->locationX;
    my $Y = $row->locationY;
    my $S = $row->regionsize / 256;
    my $size;
    if ( $S <= 3 ) {
        $size = $row->regionsize;
    }
    else {
        $size = 768;
    }

    my $count = $schema->resultset('Visitor')
      ->search( { regionname => $row->regionname })->count;
   
    my $mapfile = $path . '/maps/' . $row->regionname . '.png';
    if ( -e $mapfile ) {
        $file = '/Stats/maps/' . $row->regionname . '.png';
    }
    else {
        $file = '/Stats/images/blankbox.jpg';
    }

    push @sims,
      {
        regionname => $row->regionname,
        regionsize => $row->regionsize . " X " . $row->regionsize,
        map        => $file,
        width      => $size,
        link       => '/Stats/map.htm?q=' . $row->regionname,
        count      => $count,
      };

}

if ( !@sims ) {
    push @sims,
      {
        regionname => 'No Regions Found',
        regionsize => 0,
        map        => '/Stats/images/blankbox.jpg',
        width      => 256,
        count      => 0,
      };

}
my $vars = { sims => \@sims };
my $out;
$tt->process( 'listmaps.tt', $vars, \$out ) || die $tt->error(), "\n";

print $out;
