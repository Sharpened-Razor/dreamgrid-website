use utf8;
package Opensim::Schema::Result::Land;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Land

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<land>

=cut

__PACKAGE__->table("land");

=head1 ACCESSORS

=head2 uuid

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 regionuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 locallandid

  data_type: 'integer'
  is_nullable: 1

=head2 bitmap

  data_type: 'longblob'
  is_nullable: 1

=head2 name

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 description

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 owneruuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 isgroupowned

  data_type: 'integer'
  is_nullable: 1

=head2 area

  data_type: 'integer'
  is_nullable: 1

=head2 auctionid

  data_type: 'integer'
  is_nullable: 1

=head2 category

  data_type: 'integer'
  is_nullable: 1

=head2 claimdate

  data_type: 'integer'
  is_nullable: 1

=head2 claimprice

  data_type: 'integer'
  is_nullable: 1

=head2 groupuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 saleprice

  data_type: 'integer'
  is_nullable: 1

=head2 landstatus

  data_type: 'integer'
  is_nullable: 1

=head2 landflags

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 1

=head2 landingtype

  data_type: 'integer'
  is_nullable: 1

=head2 mediaautoscale

  data_type: 'integer'
  is_nullable: 1

=head2 mediatextureuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 mediaurl

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 musicurl

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 passhours

  data_type: 'float'
  is_nullable: 1

=head2 passprice

  data_type: 'integer'
  is_nullable: 1

=head2 snapshotuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 userlocationx

  data_type: 'float'
  is_nullable: 1

=head2 userlocationy

  data_type: 'float'
  is_nullable: 1

=head2 userlocationz

  data_type: 'float'
  is_nullable: 1

=head2 userlookatx

  data_type: 'float'
  is_nullable: 1

=head2 userlookaty

  data_type: 'float'
  is_nullable: 1

=head2 userlookatz

  data_type: 'float'
  is_nullable: 1

=head2 authbuyerid

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 othercleantime

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 dwell

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 mediatype

  data_type: 'varchar'
  default_value: 'none/none'
  is_nullable: 0
  size: 32

=head2 mediadescription

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 mediasize

  data_type: 'varchar'
  default_value: '0,0'
  is_nullable: 0
  size: 16

=head2 medialoop

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 obscuremusic

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 obscuremedia

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 seeavs

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 anyavsounds

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 groupavsounds

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 environment

  data_type: 'mediumtext'
  is_nullable: 1

=cut

__PACKAGE__->add_columns(
  "uuid",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "regionuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "locallandid",
  { data_type => "integer", is_nullable => 1 },
  "bitmap",
  { data_type => "longblob", is_nullable => 1 },
  "name",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "description",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "owneruuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "isgroupowned",
  { data_type => "integer", is_nullable => 1 },
  "area",
  { data_type => "integer", is_nullable => 1 },
  "auctionid",
  { data_type => "integer", is_nullable => 1 },
  "category",
  { data_type => "integer", is_nullable => 1 },
  "claimdate",
  { data_type => "integer", is_nullable => 1 },
  "claimprice",
  { data_type => "integer", is_nullable => 1 },
  "groupuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "saleprice",
  { data_type => "integer", is_nullable => 1 },
  "landstatus",
  { data_type => "integer", is_nullable => 1 },
  "landflags",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 1 },
  "landingtype",
  { data_type => "integer", is_nullable => 1 },
  "mediaautoscale",
  { data_type => "integer", is_nullable => 1 },
  "mediatextureuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "mediaurl",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "musicurl",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "passhours",
  { data_type => "float", is_nullable => 1 },
  "passprice",
  { data_type => "integer", is_nullable => 1 },
  "snapshotuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "userlocationx",
  { data_type => "float", is_nullable => 1 },
  "userlocationy",
  { data_type => "float", is_nullable => 1 },
  "userlocationz",
  { data_type => "float", is_nullable => 1 },
  "userlookatx",
  { data_type => "float", is_nullable => 1 },
  "userlookaty",
  { data_type => "float", is_nullable => 1 },
  "userlookatz",
  { data_type => "float", is_nullable => 1 },
  "authbuyerid",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "othercleantime",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "dwell",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "mediatype",
  {
    data_type => "varchar",
    default_value => "none/none",
    is_nullable => 0,
    size => 32,
  },
  "mediadescription",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "mediasize",
  {
    data_type => "varchar",
    default_value => "0,0",
    is_nullable => 0,
    size => 16,
  },
  "medialoop",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "obscuremusic",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "obscuremedia",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "seeavs",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "anyavsounds",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "groupavsounds",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "environment",
  { data_type => "mediumtext", is_nullable => 1 },
);

=head1 PRIMARY KEY

=over 4

=item * L</uuid>

=back

=cut

__PACKAGE__->set_primary_key("uuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:UgIjX2n9jqRwGrhCHtAAhQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
