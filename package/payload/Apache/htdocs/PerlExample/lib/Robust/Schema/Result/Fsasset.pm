use utf8;
package Robust::Schema::Result::Fsasset;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Fsasset

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<fsassets>

=cut

__PACKAGE__->table("fsassets");

=head1 ACCESSORS

=head2 id

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 name

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 64

=head2 description

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 64

=head2 type

  data_type: 'integer'
  is_nullable: 0

=head2 hash

  data_type: 'char'
  is_nullable: 0
  size: 80

=head2 create_time

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 access_time

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 asset_flags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "id",
  { data_type => "char", is_nullable => 0, size => 36 },
  "name",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 64 },
  "description",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 64 },
  "type",
  { data_type => "integer", is_nullable => 0 },
  "hash",
  { data_type => "char", is_nullable => 0, size => 80 },
  "create_time",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "access_time",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "asset_flags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</id>

=back

=cut

__PACKAGE__->set_primary_key("id");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:mwvq3+nU/foGhFKmZogxrA


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
