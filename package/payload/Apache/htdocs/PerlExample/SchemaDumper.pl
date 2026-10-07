# dump the MySQL database to DBIX::Class result objects ibn lib/Opensim/Schema/Result. Needs to be run only if the db schema has changed

use lib qw(lib); # look in lib for modules and databases
use DBIx::Class::Schema::Loader qw /make_schema_at dump_to_dir/;
make_schema_at("Opensim::Schema",
               { debug => 1 ,dump_directory => './lib'},
               [ "dbi:mysql:dbname=opensim","opensimuser", "opensimpassword" ]
               );

use DBIx::Class::Schema::Loader qw /make_schema_at dump_to_dir/;
make_schema_at("Robust::Schema",
               { debug => 1 ,dump_directory => './lib'},
               [ "dbi:mysql:dbname=robust","robustuser", "robustpassword" ]
               );

