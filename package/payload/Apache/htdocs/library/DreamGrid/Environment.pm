package DreamGrid::Environment;
use strict; use warnings; use Cwd qw(abs_path getcwd); use File::Basename qw(dirname); use File::Spec;
sub root {
    my $dir=abs_path($_[0] || getcwd()) or die "Working directory could not be resolved";
    while(!-f File::Spec->catfile($dir,'Settings.ini')){my $parent=dirname($dir);die "DreamGrid settings not found" if $parent eq $dir;$dir=$parent;}
    return $dir;
}
sub ini {
    my($file)=@_;open(my $fh,'<:encoding(UTF-8)',$file) or die "Grid configuration unavailable";
    my(%ini,$section);$section='';while(my $line=<$fh>){$line=~s/^\x{FEFF}//;if($line=~/^\s*\[([^\]]+)\]/){$section=lc($1);next;}
      if($line=~/^\s*([^;#=]+?)\s*=\s*(.*?)\s*$/){my($key,$value)=(lc($1),$2);$value=~s/^(?:"(.*)"|'(.*)')$/$1\/\/ $2/e;$ini{$section}{$key}=$value;}}
    close $fh;return \%ini;
}
sub value {
    my($ini,$section,$key)=@_;my $v=$ini->{lc($section)}{lc($key)};return undef unless defined $v;
    for(1..16){last unless $v=~/\$\{/;$v=~s/\$\{([^|}]+)\|([^}]+)\}/$ini->{lc($1)}{lc($2)}\/\/die("Unresolved configuration")/ge;}return $v;
}
sub database {
    my($root)=@_;my $native=ini(File::Spec->catfile($root,'Opensim','bin','Robust.HG.ini'));
    my $connection=value($native,'DatabaseService','ConnectionString') or die "Native database configuration missing";
    my %parts;for(split(/;/,$connection)){if(/^\s*([^=]+?)\s*=\s*(.*?)\s*$/){$parts{lc($1)}=$2;}}
    my %result=(host=>$parts{'data source'}//$parts{'server'}//$parts{'host'},database=>$parts{'database'}//$parts{'initial catalog'},user=>$parts{'user id'}//$parts{'uid'}//$parts{'user'},password=>$parts{'password'}//$parts{'pwd'},port=>$parts{'port'});
    if(!$result{port}){my $settings=ini(File::Spec->catfile($root,'Settings.ini'));$result{port}=value($settings,'Data','MySqlRobustDBPort');}
    for(qw(host database user password port)){die "Incomplete native database configuration" unless defined $result{$_};}return \%result;
}
sub local_request {
    my $remote=$ENV{REMOTE_ADDR}//'';my $server=$ENV{SERVER_ADDR}//'';
    return $remote ne '' && $server ne '' && $remote eq $server;
}
1;
