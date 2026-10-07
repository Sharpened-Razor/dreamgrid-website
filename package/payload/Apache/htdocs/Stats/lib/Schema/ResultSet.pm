package SecondLife::Schema::ResultSet;

use parent   'Lib::Schema::ResultSet';

__PACKAGE__->load_components(qw(
   Helper::ResultSet::IgnoreWantarray
   Helper::ResultSet::SetOperations
   Helper::ResultSet::ResultClassDWIM
   Helper::ResultSet::Me
));

1;
