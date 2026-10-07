package Robust::Util;
use strict;
use warnings;

# Read your Opensim database name, username, and password.

sub mysql_connect {
        
   use lib qw (. lib);
   use Config::IniFiles;
   use File::BOM;  # fixes a bug in Perl with UTF-8
   
    use File::Basename qw(dirname);use File::Spec;use Cwd qw(getcwd abs_path);
    my $root=abs_path(getcwd());
    while(!-f File::Spec->catfile($root,'Settings.ini')){my $parent=dirname($root);die "Grid settings not found" if $parent eq $root;$root=$parent;}
    require File::Spec->catfile($root,'Apache','htdocs','library','DreamGrid','Environment.pm');
    my $database=DreamGrid::Environment::database($root);
    my($dbname,$port,$host,$user,$password)=@{$database}{qw(database port host user password)};
   require Robust::Schema;
   if ($dbname) {
      Robust::Schema->connect->connect("dbi:mysql:dbname=$dbname;host=$host;port=$port",$user,$password,{quote_names => 1,});
   }
   
}

1;


