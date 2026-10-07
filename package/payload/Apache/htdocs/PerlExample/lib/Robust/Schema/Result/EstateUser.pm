use utf8;
package Robust::Schema::Result::EstateUser;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::EstateUser

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<estate_users>

=cut

__PACKAGE__->table("estate_users");

=head1 ACCESSORS

=head2 estateid

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 0

=head2 uuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=cut

__PACKAGE__->add_columns(
  "estateid",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 0 },
  "uuid",
  { data_type => "char", is_nullable => 0, size => 36 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:eJxS3hw0h1m2pbxDw53nMQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
