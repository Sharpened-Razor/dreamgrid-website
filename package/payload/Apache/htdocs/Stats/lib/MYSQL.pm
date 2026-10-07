package	MYSQL;

use warnings;
use strict;

my $LOG = '';
use DBI;
my $connect_line;

sub new {

    #DSN=robust:localhost:3306;UID=robustuser;PWD=robustpassword;

    if ( open( FILE, "../../MySQL.txt" ) ) {
        while (<FILE>) {
            $connect_line = $_;
        }
    }
    else { die $!; }

    my $self = {};

    $_ = $connect_line;
    $self->{ODBC_DSN} = $_;

    if ( $connect_line =~ /DSN=(.*?);UID=(.*?);PWD=(.*?);/i ) {

        #---- DBI CONNECTION VARIABLES
        $self->{ODBC_DSN} = $1;
        $self->{ODBC_UID} = $2;
        $self->{ODBC_PWD} = $3;
    }

    #---- DBI CONNECTION VARIABLES
    $self->{DBI_DBNAME}   = $self->{ODBC_DSN};
    $self->{DBI_USER}     = $self->{ODBC_UID};
    $self->{DBI_PASSWORD} = $self->{ODBC_PWD};
    $self->{DBI_DBD}      = 'ODBC';

    my $Connect = "dbi:mysql:$self->{'DBI_DBNAME'}";

    #---- DBI CONNECTION
    $self->{'DBI_DBH'} = DBI->connect(
        $Connect,
        $self->{'DBI_USER'},
        $self->{'DBI_PASSWORD'},
        {
            RaiseError  => 0,
            AutoCommit  => 1,
            LongReadLen => 256000,
            LongTruncOk => 1,
            PrintError  => 0,
            ChopBlanks  => 1
        }
    );

    # Status and messages can be much longer now - up to 4K.
    # Added LongReadLen => 4000
    #LongTruncOk=>1 means it is okay to toss longer data
    # PrintError=>0 says not to complain about it
    # so if the data read back is > 4000, do not throw an error.

    if ( !$self->{'DBI_DBH'} ) {
        warn "Error($DBI::err) : $DBI::errstr\n"
          unless ( length( $ENV{REMOTE_ADDR} ) > 0 )
          ;    # supress warns in browsers

        $self->DBIODBCErrorLog(
            "->New($connect_line) Error= Error($DBI::err) : $DBI::errstr");

        return undef;    # return null for on ->New()
    }

    # uncomment to see trace results

    #$self->{'DBI_DBH'}->{TraceLevel} = "2";

    bless $self;
}

sub GetDate {

    my (
        $sec,  $min,  $hour,  $mday, $mon,  $year,
        $wday, $yday, $isdst, $Date, $Time, $now
    );

    ( $sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst ) =
      localtime(time);
    $year = $year + 1900;
    $mon++;

    my $Date1 = $mon . '/' . $mday . '/' . $year;
    my $Time1 = $hour . ':' . $min . ':' . $sec;

    my $now1 = $Date1 . " " . $Time1;

    return ($now1);
}

sub GetSQL {
    my $self = shift;
    return $self->{'DBI_SQL_STATMENT'};
}

#EMU --- $db->Sql('SELECT * FROM DUAL');
sub Sql {
    my $self         = shift;
    my $SQL_statment = shift;

    $self->{'DBI_SQL_STATMENT'} = $SQL_statment;
    DBIODBCLog($SQL_statment);

    my $dbh = $self->{'DBI_DBH'};

    my $sth = $dbh->prepare($SQL_statment);

    $self->{'DBI_STH'} = $sth;

    if ($sth) {
        $sth->execute();
    }

    #--- GET ERROR MESSAGES
    $self->{DBI_ERR}    = $DBI::err;
    $self->{DBI_ERRSTR} = $DBI::errstr;
    $self->DBIODBCErrorLog(
        "->Sql($SQL_statment) Error= Error($DBI::err) : $DBI::errstr")
      if $DBI::err;

    if ($sth) {

        #--- GET COLUMNS NAMES
        $self->{'DBI_NAME'} = $sth->{NAME};
    }

# [R] provide compatibility with My::DBIODBC's way of identifying erroneous SQL statements
    return ( $self->{'DBI_ERR'} ) ? 1 : undef;

    # -[R]-
}

sub DBIODBCErrorLog {
    my $self = shift;
    my $msg  = shift;

    if ($LOG) {
        if ( open( LOGFILE, ">>$LOG" ) ) {
            my $Date;
            $Date = GetDate();
            $Date =~ s/\//-/g;
            $msg =~ s/^\s+//g;
            $msg =~ s/\n/ /g;
            $msg =~ s/\r//g;
            print LOGFILE "$Date:$$:$msg\n";
            close(LOGFILE);
        }
    }
}

sub DBIODBCLog {

    my $msg = shift;

    if ( open( LOGFILE, ">>$LOG" ) ) {
        my $Date;
        $Date = GetDate();
        $Date =~ s/\//-/g;
        $msg =~ s/^\s+//g;
        $msg =~ s/\n/ /g;
        $msg =~ s/\r//g;
        print LOGFILE "$Date:$$:$msg\n";
        close(LOGFILE);
    }

}

#EMU --- $db->FetchRow())
sub FetchRow {
    my $self = shift;

    my $sth = $self->{'DBI_STH'};
    if ($sth) {
        my @row = $sth->fetchrow_array;
        $self->{'DBI_ROW'} = \@row;

        if ( scalar(@row) > 0 ) {

            #-- the row of result is not nul
            #-- return something nothing will be return else
            return 1;
        }
    }
    return undef;
}

# [R] provide compatibility with My::DBIODBC's Data() method.
sub Data {
    my $self  = shift;
    my @array = @{ $self->{'DBI_ROW'} };
    foreach my $element (@array) {

        # remove padding of spaces by DBI
        $element =~ s/(\s*$)//;
    }
    return ( wantarray() ) ? @array : join( '', @array );
}

# -[R]-

#EMU --- %record = $db->DataHash;
sub DataHash {
    my $self = shift;

    my $p_name = $self->{'DBI_NAME'};
    my $p_row  = $self->{'DBI_ROW'};

    my @name = @$p_name;
    my @row  = @$p_row;

    my %DataHash;

    # [R] new code that seems to work consistent with My::DBIODBC
    while (@name) {
        my $name  = shift(@name);
        my $value = shift(@row);

        # remove padding of spaces by DBI
        #$name=~s/(\s*$)//;
        #$value=~s/(\s*$)//;

        # fix up doubled single quotes
        #$value=~s/\'\'/\'/g;

        $value = '' if !defined $value;

        $DataHash{$name} = $value;
    }

    #--- Return Hash
    return %DataHash;
}

#EMU --- $db->Error()
sub Error {
    my $self = shift;

    if ( $self->{'DBI_ERR'} ne '' ) {
        $self->DBIODBCErrorLog(
"Warning from DBIODBC.pm for ($self->{'DBI_SQL_STATMENT'}) of $self->{'DBI_ERRSTR'}"
        );

        #--- Return error message
        $self->{'DBI_ERRSTR'};
    }

    #-- else good no error message
}

# provide compatibility with My::DBIODBC's Close() method.
sub Close {
    my $self = shift;

    # [FKB] must call finish method before destroying with later DBI module
    my $sth = $self->{'DBI_STH'};
    $sth->finish()
      if $self->{'DBI_SQL_STATMENT'}
      ;    #Cannot  call finish method if we have no sql statement

    my $dbh = $self->{'DBI_DBH'};
    $dbh->disconnect;
}

sub Finish {
    my $self = shift;
    my $sth  = $self->{'DBI_STH'};
    $sth->finish()
      if $self->{'DBI_SQL_STATMENT'}
      ;    #Cannot  call finish method if we have no sql statement
}

sub Prepare {
    my $self         = shift;
    my $SQL_statment = shift;

    $self->{'DBI_SQL_STATMENT'} = $SQL_statment;
    DBIODBCLog($SQL_statment);

    my $dbh = $self->{'DBI_DBH'};
    my %attr;    # no attributes

=pod
The last  parameter lets you adjust the behaviour if an already cached statement handle is still Active. There are several alternatives:

0: A warning will be generated, and finish() will be called on the statement handle before it is returned. This is the default behaviour if $if_active is not passed.
1: finish() will be called on the statement handle, but the warning is suppressed.
2: Disables any checking.
3: The existing active statement handle will be removed from the cache and a new statement handle prepared and cached in its place. This is the safest option because it doesn't affect the state of the old handle, it just removes it from the cache. [Added in DBI 1.40]
=cut

    my $sth = $dbh->prepare_cached( $SQL_statment, \%attr, 1 );

    if ( $self->{'DBI_STH'} = $sth ) {
        return;
    }

    1;    # error, no statement handle returned
}

sub Execute {
    my $self = shift;
    my $param;
    my @values = @_;

    my $sth = $self->{'DBI_STH'};

    $sth->execute(@values) if $sth;

    #--- GET ERROR MESSAGES
    $self->{DBI_ERR}    = $DBI::err;
    $self->{DBI_ERRSTR} = $DBI::errstr;

    $self->DBIODBCErrorLog(
"->Sql($self->{'DBI_SQL_STATMENT'}) Error= Error($DBI::err) : $DBI::errstr"
    ) if $DBI::err;

    if ($sth) {

        #--- GET COLUMNS NAMES
        $self->{'DBI_NAME'} = $sth->{NAME};
    }

# provide compatibility with My::DBIODBC's way of identifying erroneous SQL statements
    return ( $self->{'DBI_ERR'} ) ? 1 : undef;

}

sub Do {
    my $self = shift;
    my $param;
    my $sql = @_;

    my $sth = $self->{'DBI_STH'};

    $sth->do($sql) if $sth;

    #--- GET ERROR MESSAGES
    $self->{DBI_ERR}    = $DBI::err;
    $self->{DBI_ERRSTR} = $DBI::errstr;

    $self->DBIODBCErrorLog(
"->Sql($self->{'DBI_SQL_STATMENT'}) Error= Error($DBI::err) : $DBI::errstr"
    ) if $DBI::err;

    if ($sth) {

        #--- GET COLUMNS NAMES
        $self->{'DBI_NAME'} = $sth->{NAME};
    }

# provide compatibility with My::DBIODBC's way of identifying erroneous SQL statements
    return ( $self->{'DBI_ERR'} ) ? 1 : undef;

}

sub begin_work {
    my $self = shift;
    my $sth  = $self->{'DBI_DBH'};
    $sth->begin_work() if $sth;
    return ( $self->{'DBI_ERR'} ) ? 1 : undef;

}

sub commit {
    my $self = shift;

    my $sth = $self->{'DBI_DBH'};
    $sth->commit() if $sth;
    return ( $self->{'DBI_ERR'} ) ? 1 : undef;

}

sub Print_ODBC_Error {

    my $Errornum;

    # if $ExitandDie flag, die to restart services,
    # if not, just exit to not kill web server

    $Errornum = Error();
    my $sql = GetSQL();
    our $error;
    if ($Errornum) {
        my $error = "PID $$:Fatal Error $Errornum\n";
        print "$error: $Errornum";
    }
    else {
        $error = "PID $$:Unknown Error \n";
        print "$error: $Errornum";
    }

    exit;    # just leave

}

1;

__END__

#
=head1 NAME

My::DBIODBC - My::DBIODBC emulation layer for the DBI

=head1 SYNOPSIS

  use My::DBIODBC;     # instead of use My::DBIODBC

=head1 DESCRIPTION

This is a My::DBIODBC wrapper for the DBI.

=head1 AUTHOR

fred.b@mitsi.com

=cut
