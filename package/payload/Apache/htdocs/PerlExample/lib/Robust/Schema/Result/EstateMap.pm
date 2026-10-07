use utf8;
package Robust::Schema::Result::EstateMap;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::EstateMap

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<estate_map>

=cut

__PACKAGE__->table("estate_map");

=head1 ACCESSORS

=head2 regionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 estateid

  data_type: 'integer'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "regionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "estateid",
  { data_type => "integer", is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</regionid>

=back

=cut

__PACKAGE__->set_primary_key("regionid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:+DW5ISvACeSAtv64x4lwMg


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
