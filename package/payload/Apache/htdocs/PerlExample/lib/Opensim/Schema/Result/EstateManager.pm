use utf8;
package Opensim::Schema::Result::EstateManager;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::EstateManager

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<estate_managers>

=cut

__PACKAGE__->table("estate_managers");

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


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:yeIAJvvvcAdplkIrQKi2Zw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
