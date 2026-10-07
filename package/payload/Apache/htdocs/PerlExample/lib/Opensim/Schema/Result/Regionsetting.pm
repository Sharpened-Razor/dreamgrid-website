use utf8;
package Opensim::Schema::Result::Regionsetting;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Regionsetting

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<regionsettings>

=cut

__PACKAGE__->table("regionsettings");

=head1 ACCESSORS

=head2 regionuuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 block_terraform

  data_type: 'integer'
  is_nullable: 0

=head2 block_fly

  data_type: 'integer'
  is_nullable: 0

=head2 allow_damage

  data_type: 'integer'
  is_nullable: 0

=head2 restrict_pushing

  data_type: 'integer'
  is_nullable: 0

=head2 allow_land_resell

  data_type: 'integer'
  is_nullable: 0

=head2 allow_land_join_divide

  data_type: 'integer'
  is_nullable: 0

=head2 block_show_in_search

  data_type: 'integer'
  is_nullable: 0

=head2 agent_limit

  data_type: 'integer'
  is_nullable: 0

=head2 object_bonus

  data_type: 'double precision'
  is_nullable: 0

=head2 maturity

  data_type: 'integer'
  is_nullable: 0

=head2 disable_scripts

  data_type: 'integer'
  is_nullable: 0

=head2 disable_collisions

  data_type: 'integer'
  is_nullable: 0

=head2 disable_physics

  data_type: 'integer'
  is_nullable: 0

=head2 terrain_texture_1

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 terrain_texture_2

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 terrain_texture_3

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 terrain_texture_4

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 elevation_1_nw

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_2_nw

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_1_ne

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_2_ne

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_1_se

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_2_se

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_1_sw

  data_type: 'double precision'
  is_nullable: 0

=head2 elevation_2_sw

  data_type: 'double precision'
  is_nullable: 0

=head2 water_height

  data_type: 'double precision'
  is_nullable: 0

=head2 terrain_raise_limit

  data_type: 'double precision'
  is_nullable: 0

=head2 terrain_lower_limit

  data_type: 'double precision'
  is_nullable: 0

=head2 use_estate_sun

  data_type: 'integer'
  is_nullable: 0

=head2 fixed_sun

  data_type: 'integer'
  is_nullable: 0

=head2 sun_position

  data_type: 'double precision'
  is_nullable: 0

=head2 covenant

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 sandbox

  data_type: 'tinyint'
  is_nullable: 0

=head2 sunvectorx

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 sunvectory

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 sunvectorz

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 loaded_creation_id

  data_type: 'varchar'
  is_nullable: 1
  size: 64

=head2 loaded_creation_datetime

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 map_tile_id

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 telehubobject

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 parcel_tile_id

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 covenant_datetime

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 block_search

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 casino

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 cacheid

  data_type: 'char'
  is_nullable: 1
  size: 36

=cut

__PACKAGE__->add_columns(
  "regionuuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "block_terraform",
  { data_type => "integer", is_nullable => 0 },
  "block_fly",
  { data_type => "integer", is_nullable => 0 },
  "allow_damage",
  { data_type => "integer", is_nullable => 0 },
  "restrict_pushing",
  { data_type => "integer", is_nullable => 0 },
  "allow_land_resell",
  { data_type => "integer", is_nullable => 0 },
  "allow_land_join_divide",
  { data_type => "integer", is_nullable => 0 },
  "block_show_in_search",
  { data_type => "integer", is_nullable => 0 },
  "agent_limit",
  { data_type => "integer", is_nullable => 0 },
  "object_bonus",
  { data_type => "double precision", is_nullable => 0 },
  "maturity",
  { data_type => "integer", is_nullable => 0 },
  "disable_scripts",
  { data_type => "integer", is_nullable => 0 },
  "disable_collisions",
  { data_type => "integer", is_nullable => 0 },
  "disable_physics",
  { data_type => "integer", is_nullable => 0 },
  "terrain_texture_1",
  { data_type => "char", is_nullable => 0, size => 36 },
  "terrain_texture_2",
  { data_type => "char", is_nullable => 0, size => 36 },
  "terrain_texture_3",
  { data_type => "char", is_nullable => 0, size => 36 },
  "terrain_texture_4",
  { data_type => "char", is_nullable => 0, size => 36 },
  "elevation_1_nw",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_2_nw",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_1_ne",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_2_ne",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_1_se",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_2_se",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_1_sw",
  { data_type => "double precision", is_nullable => 0 },
  "elevation_2_sw",
  { data_type => "double precision", is_nullable => 0 },
  "water_height",
  { data_type => "double precision", is_nullable => 0 },
  "terrain_raise_limit",
  { data_type => "double precision", is_nullable => 0 },
  "terrain_lower_limit",
  { data_type => "double precision", is_nullable => 0 },
  "use_estate_sun",
  { data_type => "integer", is_nullable => 0 },
  "fixed_sun",
  { data_type => "integer", is_nullable => 0 },
  "sun_position",
  { data_type => "double precision", is_nullable => 0 },
  "covenant",
  { data_type => "char", is_nullable => 1, size => 36 },
  "sandbox",
  { data_type => "tinyint", is_nullable => 0 },
  "sunvectorx",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "sunvectory",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "sunvectorz",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "loaded_creation_id",
  { data_type => "varchar", is_nullable => 1, size => 64 },
  "loaded_creation_datetime",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "map_tile_id",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "telehubobject",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "parcel_tile_id",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "covenant_datetime",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "block_search",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "casino",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "cacheid",
  { data_type => "char", is_nullable => 1, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</regionuuid>

=back

=cut

__PACKAGE__->set_primary_key("regionuuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:dtvP5oH749WvLaPE4wE9Ig


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
