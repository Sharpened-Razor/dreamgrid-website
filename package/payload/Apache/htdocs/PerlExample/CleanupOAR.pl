#  Strawberry Perl program to save one backup per month into a different folder

use 5.010;
use strict;
use warnings;
use IO::All;
use File::Copy qw(move);
use File::Basename;
use CGI;
$| = 1;  #non buffered out


# fill these in with $src and $Destination paths.
my ($src,$dest)=@ARGV; die "Usage: CleanupOAR.pl source_directory archive_directory\n" unless defined($src) && defined($dest);

my @f    = io->dir($src)->all;

my %result;

foreach my $folder (sort @f) {
    
    my @files    = io->dir($folder)->all;    
      
    foreach my $file (sort @files) {            
        
        $file->name =~ /(.*?)_(\d{4})y_(\d{2})M_(\d{2})d_|(.*?)_(\d{4})-(\d{2})-(\d{2})_/;
        my $name = $1 || $5;
        if ($name) {
            $name = basename($name);
            next unless $name;
        }
        my $yyyy = $2 || $6;
        my $mm = $3 || $7;
        my $dd = $4 || $8;
        
        if ($name && $yyyy && $mm && $dd ) {        
            if ($result{"$name $yyyy-$mm"})
            { 
                say("Move $name $yyyy-$mm              " . $file->name);
                my $newname = $file->name;
                $newname =~ s/\\/\//g;
                $newname =~ s/$src/$dest/;
                my $srcname = dirname($newname);
                mkdir $srcname;
                move $file->name, $newname || die $!;
            } else {
                say("Keep $name $yyyy-$mm " . $file->name);
                
                $result{"$name $yyyy-$mm"} = $file->name;    
            }                
        }
    }

}
