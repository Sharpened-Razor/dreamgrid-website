use utf8;
package Opensim::Schema::Result::EstateSetting;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::EstateSetting

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<estate_settings>

=cut

__PACKAGE__->table("estate_settings");

=head1 ACCESSORS

=head2 estateid

  data_type: 'integer'
  extra: {unsigned => 1}
  is_auto_increment: 1
  is_nullable: 0

=head2 estatename

  data_type: 'varchar'
  is_nullable: 1
  size: 64

=head2 abuseemailtoestateowner

  data_type: 'tinyint'
  is_nullable: 0

=head2 denyanonymous

  data_type: 'tinyint'
  is_nullable: 0

=head2 resethomeonteleport

  data_type: 'tinyint'
  is_nullable: 0

=head2 fixedsun

  data_type: 'tinyint'
  is_nullable: 0

=head2 denytransacted

  data_type: 'tinyint'
  is_nullable: 0

=head2 blockdwell

  data_type: 'tinyint'
  is_nullable: 0

=head2 denyidentified

  data_type: 'tinyint'
  is_nullable: 0

=head2 allowvoice

  data_type: 'tinyint'
  is_nullable: 0

=head2 useglobaltime

  data_type: 'tinyint'
  is_nullable: 0

=head2 pricepermeter

  data_type: 'integer'
  is_nullable: 0

=head2 taxfree

  data_type: 'tinyint'
  is_nullable: 0

=head2 allowdirectteleport

  data_type: 'tinyint'
  is_nullable: 0

=head2 redirectgridx

  data_type: 'integer'
  is_nullable: 0

=head2 redirectgridy

  data_type: 'integer'
  is_nullable: 0

=head2 parentestateid

  data_type: 'integer'
  extra: {unsigned => 1}
  is_nullable: 0

=head2 sunposition

  data_type: 'double precision'
  is_nullable: 0

=head2 estateskipscripts

  data_type: 'tinyint'
  is_nullable: 0

=head2 billablefactor

  data_type: 'float'
  is_nullable: 0

=head2 publicaccess

  data_type: 'tinyint'
  is_nullable: 0

=head2 abuseemail

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 estateowner

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 denyminors

  data_type: 'tinyint'
  is_nullable: 0

=head2 allowlandmark

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 allowparcelchanges

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 allowsethome

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 allowenviromentoverride

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "estateid",
  {
    data_type => "integer",
    extra => { unsigned => 1 },
    is_auto_increment => 1,
    is_nullable => 0,
  },
  "estatename",
  { data_type => "varchar", is_nullable => 1, size => 64 },
  "abuseemailtoestateowner",
  { data_type => "tinyint", is_nullable => 0 },
  "denyanonymous",
  { data_type => "tinyint", is_nullable => 0 },
  "resethomeonteleport",
  { data_type => "tinyint", is_nullable => 0 },
  "fixedsun",
  { data_type => "tinyint", is_nullable => 0 },
  "denytransacted",
  { data_type => "tinyint", is_nullable => 0 },
  "blockdwell",
  { data_type => "tinyint", is_nullable => 0 },
  "denyidentified",
  { data_type => "tinyint", is_nullable => 0 },
  "allowvoice",
  { data_type => "tinyint", is_nullable => 0 },
  "useglobaltime",
  { data_type => "tinyint", is_nullable => 0 },
  "pricepermeter",
  { data_type => "integer", is_nullable => 0 },
  "taxfree",
  { data_type => "tinyint", is_nullable => 0 },
  "allowdirectteleport",
  { data_type => "tinyint", is_nullable => 0 },
  "redirectgridx",
  { data_type => "integer", is_nullable => 0 },
  "redirectgridy",
  { data_type => "integer", is_nullable => 0 },
  "parentestateid",
  { data_type => "integer", extra => { unsigned => 1 }, is_nullable => 0 },
  "sunposition",
  { data_type => "double precision", is_nullable => 0 },
  "estateskipscripts",
  { data_type => "tinyint", is_nullable => 0 },
  "billablefactor",
  { data_type => "float", is_nullable => 0 },
  "publicaccess",
  { data_type => "tinyint", is_nullable => 0 },
  "abuseemail",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "estateowner",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "denyminors",
  { data_type => "tinyint", is_nullable => 0 },
  "allowlandmark",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "allowparcelchanges",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "allowsethome",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "allowenviromentoverride",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</estateid>

=back

=cut

__PACKAGE__->set_primary_key("estateid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:+D8dHw7VRc5XQh4gkoue6g


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
