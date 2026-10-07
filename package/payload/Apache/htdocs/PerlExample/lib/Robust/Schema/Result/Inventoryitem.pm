use utf8;
package Robust::Schema::Result::Inventoryitem;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Inventoryitem

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<inventoryitems>

=cut

__PACKAGE__->table("inventoryitems");

=head1 ACCESSORS

=head2 assetid

  data_type: 'varchar'
  is_nullable: 1
  size: 36

=head2 assettype

  data_type: 'integer'
  is_nullable: 1

=head2 inventoryname

  data_type: 'varchar'
  is_nullable: 1
  size: 64

=head2 inventorydescription

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 inventorynextpermissions

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 inventorycurrentpermissions

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 invtype

  data_type: 'integer'
  is_nullable: 1

=head2 creatorid

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 255

=head2 inventorybasepermissions

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 inventoryeveryonepermissions

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 saleprice

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 saletype

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 creationdate

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 groupid

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 groupowned

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 flags

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 inventoryid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 avatarid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 parentfolderid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 inventorygrouppermissions

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "assetid",
  { data_type => "varchar", is_nullable => 1, size => 36 },
  "assettype",
  { data_type => "integer", is_nullable => 1 },
  "inventoryname",
  { data_type => "varchar", is_nullable => 1, size => 64 },
  "inventorydescription",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "inventorynextpermissions",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "inventorycurrentpermissions",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "invtype",
  { data_type => "integer", is_nullable => 1 },
  "creatorid",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 255,
  },
  "inventorybasepermissions",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "inventoryeveryonepermissions",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "saleprice",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "saletype",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "creationdate",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "groupid",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "groupowned",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "flags",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "inventoryid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "avatarid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "parentfolderid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "inventorygrouppermissions",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</inventoryid>

=back

=cut

__PACKAGE__->set_primary_key("inventoryid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:tKleIfAV4Tb82lc3PoISyw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
