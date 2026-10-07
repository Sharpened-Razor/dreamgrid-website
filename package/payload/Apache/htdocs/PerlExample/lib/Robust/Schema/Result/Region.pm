use utf8;
package Robust::Schema::Result::Region;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Region

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<regions>

=cut

__PACKAGE__->table("regions");

=head1 ACCESSORS

=head2 uuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 regionhandle

  data_type: 'bigint'
  extra: {unsigned => 1}
  is_nullable: 0

=head2 regionname

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionrecvkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionsendkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionsecret

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regiondatauri

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 serverip

  data_type: 'varchar'
  is_nullable: 1
  size: 64

=head2 serverport

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 serveruri

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 locx

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 locy

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 locz

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 eastoverridehandle

  data_type: 'bigint'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 westoverridehandle

  data_type: 'bigint'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 southoverridehandle

  data_type: 'bigint'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 northoverridehandle

  data_type: 'bigint'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 regionasseturi

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 regionassetrecvkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionassetsendkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionuseruri

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 regionuserrecvkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionusersendkey

  data_type: 'varchar'
  is_nullable: 1
  size: 128

=head2 regionmaptexture

  data_type: 'varchar'
  is_nullable: 1
  size: 36

=head2 serverhttpport

  data_type: 'integer'
  is_nullable: 1

=head2 serverremotingport

  data_type: 'integer'
  is_nullable: 1

=head2 owner_uuid

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 originuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 36

=head2 access

  data_type: 'integer'
  default_value: 1
  extra: {unsigned => 1}
  is_nullable: 1

=head2 scopeid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 sizex

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 sizey

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 flags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 last_seen

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 principalid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 token

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 parcelmaptexture

  data_type: 'varchar'
  is_nullable: 1
  size: 36

=cut

__PACKAGE__->add_columns(
  "uuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "regionhandle",
  { data_type => "bigint", extra => { unsigned => 1 }, is_nullable => 0 },
  "regionname",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionrecvkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionsendkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionsecret",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regiondatauri",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "serverip",
  { data_type => "varchar", is_nullable => 1, size => 64 },
  "serverport",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "serveruri",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "locx",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "locy",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "locz",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "eastoverridehandle",
  { data_type => "bigint", extra => { unsigned => 1 }, is_nullable => 1 },
  "westoverridehandle",
  { data_type => "bigint", extra => { unsigned => 1 }, is_nullable => 1 },
  "southoverridehandle",
  { data_type => "bigint", extra => { unsigned => 1 }, is_nullable => 1 },
  "northoverridehandle",
  { data_type => "bigint", extra => { unsigned => 1 }, is_nullable => 1 },
  "regionasseturi",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "regionassetrecvkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionassetsendkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionuseruri",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "regionuserrecvkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionusersendkey",
  { data_type => "varchar", is_nullable => 1, size => 128 },
  "regionmaptexture",
  { data_type => "varchar", is_nullable => 1, size => 36 },
  "serverhttpport",
  { data_type => "integer", is_nullable => 1 },
  "serverremotingport",
  { data_type => "integer", is_nullable => 1 },
  "owner_uuid",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "originuuid",
  { data_type => "varchar", is_nullable => 1, size => 36 },
  "access",
  {
    data_type => "integer",
    default_value => 1,
    extra => { unsigned => 1 },
    is_nullable => 1,
  },
  "scopeid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "sizex",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "sizey",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "flags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "last_seen",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "principalid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "token",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "parcelmaptexture",
  { data_type => "varchar", is_nullable => 1, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</uuid>

=back

=cut

__PACKAGE__->set_primary_key("uuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:OANRXsIjbVHJlEbUzFQJKA


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
