
# Print 'show stats via perl on each region

use CGI qw(:standard);   # use the Common Gateway Interface to talk via the web server
# uncomment the next if you wish to run this via Apache. Commented out, it secure this example.
# if you uncomment out line example should run on Apache over the web.
#print header; # this tells the web server iots good data.  It prints the http headers, which is basically a 200 OK\n\n


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

my $Path =$Config->val( 'Data', 'Myfolder' );
# edit this to point to Opensim folder,. slashed to the right
my $Opensim=File::Spec->catdir($grid_root,'Opensim');

use Win32::GuiTest qw(FindWindowLike GetWindowText SetForegroundWindow SendKeys);
$Win32::GuiTest::debug = 0; # Set to "1" to enable verbose mode

my @sims =glob ("'$Opensim/bin/regions/*'");

foreach my $sim (sort @sims) {
    $sim =~ s/$Opensim\/bin\/Regions\///i;

    my @windows = FindWindowLike(0, $sim, "");
    for (@windows) {
        print "$_>\t'", GetWindowText($_), "'\n";
        SetForegroundWindow($_);

        SendKeys("\n{ENTER}show stats{ENTER}\n");
        <STDIN>;
    }
}
