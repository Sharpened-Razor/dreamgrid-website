use utf8;
package Robust::Schema::Result::Asset;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Asset

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<assets>

=cut

__PACKAGE__->table("assets");

=head1 ACCESSORS

=head2 name

  data_type: 'varchar'
  is_nullable: 0
  size: 64

=head2 description

  data_type: 'varchar'
  is_nullable: 0
  size: 64

=head2 assettype

  data_type: 'tinyint'
  is_nullable: 0

=head2 local

  data_type: 'tinyint'
  is_nullable: 0

=head2 temporary

  data_type: 'tinyint'
  is_nullable: 0

=head2 data

  data_type: 'longblob'
  is_nullable: 0

=head2 id

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 create_time

  data_type: 'integer'
  default_value: 0
  is_nullable: 1

=head2 access_time

  data_type: 'integer'
  default_value: 0
  is_nullable: 1

=head2 asset_flags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 creatorid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 128

=cut

__PACKAGE__->add_columns(
  "name",
  { data_type => "varchar", is_nullable => 0, size => 64 },
  "description",
  { data_type => "varchar", is_nullable => 0, size => 64 },
  "assettype",
  { data_type => "tinyint", is_nullable => 0 },
  "local",
  { data_type => "tinyint", is_nullable => 0 },
  "temporary",
  { data_type => "tinyint", is_nullable => 0 },
  "data",
  { data_type => "longblob", is_nullable => 0 },
  "id",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "create_time",
  { data_type => "integer", default_value => 0, is_nullable => 1 },
  "access_time",
  { data_type => "integer", default_value => 0, is_nullable => 1 },
  "asset_flags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "creatorid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 128 },
);

=head1 PRIMARY KEY

=over 4

=item * L</id>

=back

=cut

__PACKAGE__->set_primary_key("id");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:eug4KR+s4fnCIctRKWIcig


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
