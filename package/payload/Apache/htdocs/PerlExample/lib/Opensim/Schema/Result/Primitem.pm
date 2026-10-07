use utf8;
package Opensim::Schema::Result::Primitem;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Primitem

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<primitems>

=cut

__PACKAGE__->table("primitems");

=head1 ACCESSORS

=head2 invtype

  data_type: 'integer'
  is_nullable: 1

=head2 assettype

  data_type: 'integer'
  is_nullable: 1

=head2 name

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 description

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 creationdate

  data_type: 'bigint'
  is_nullable: 1

=head2 nextpermissions

  data_type: 'integer'
  is_nullable: 1

=head2 currentpermissions

  data_type: 'integer'
  is_nullable: 1

=head2 basepermissions

  data_type: 'integer'
  is_nullable: 1

=head2 everyonepermissions

  data_type: 'integer'
  is_nullable: 1

=head2 grouppermissions

  data_type: 'integer'
  is_nullable: 1

=head2 flags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 itemid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 primid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 assetid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 parentfolderid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 creatorid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 ownerid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 groupid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 lastownerid

  data_type: 'char'
  is_nullable: 1
  size: 36

=cut

__PACKAGE__->add_columns(
  "invtype",
  { data_type => "integer", is_nullable => 1 },
  "assettype",
  { data_type => "integer", is_nullable => 1 },
  "name",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "description",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "creationdate",
  { data_type => "bigint", is_nullable => 1 },
  "nextpermissions",
  { data_type => "integer", is_nullable => 1 },
  "currentpermissions",
  { data_type => "integer", is_nullable => 1 },
  "basepermissions",
  { data_type => "integer", is_nullable => 1 },
  "everyonepermissions",
  { data_type => "integer", is_nullable => 1 },
  "grouppermissions",
  { data_type => "integer", is_nullable => 1 },
  "flags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "itemid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "primid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "assetid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "parentfolderid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "creatorid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "ownerid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "groupid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "lastownerid",
  { data_type => "char", is_nullable => 1, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</itemid>

=back

=cut

__PACKAGE__->set_primary_key("itemid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:WnJiAdLdIqTlb6dqrDvGUw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
