
use strict;
use warnings;
use LWP::UserAgent ();
my $ua = LWP::UserAgent->new(timeout => 10);
my $url = shift @ARGV;
die "Usage: MapImage.pl texture_service_url\n" unless defined($url) && $url =~ m{^https?://};

$ua->default_header( 'Connection' => 'keep-alive' );
my $res = $ua->get( $url );
if ($res->is_success) {
    print $res->content;
}
else {
    die $res->status_line;
}