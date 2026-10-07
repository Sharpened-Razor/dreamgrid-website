use utf8;
package Opensim::Schema::Result::Landaccesslist;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Landaccesslist

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<landaccesslist>

=cut

__PACKAGE__->table("landaccesslist");

=head1 ACCESSORS

=head2 landuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 accessuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 flags

  data_type: 'integer'
  is_nullable: 1

=head2 expires

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "landuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "accessuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "flags",
  { data_type => "integer", is_nullable => 1 },
  "expires",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:h37cwq408C30chu/cWJx3g


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
