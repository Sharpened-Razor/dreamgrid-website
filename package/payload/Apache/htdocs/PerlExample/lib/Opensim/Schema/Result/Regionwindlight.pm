use utf8;
package Opensim::Schema::Result::Regionwindlight;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Regionwindlight

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<regionwindlight>

=cut

__PACKAGE__->table("regionwindlight");

=head1 ACCESSORS

=head2 region_id

  data_type: 'varchar'
  default_value: '000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 water_color_r

  data_type: 'float'
  default_value: 4.000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,6]

=head2 water_color_g

  data_type: 'float'
  default_value: 38.000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,6]

=head2 water_color_b

  data_type: 'float'
  default_value: 64.000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,6]

=head2 water_fog_density_exponent

  data_type: 'float'
  default_value: 4.0000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,7]

=head2 underwater_fog_modifier

  data_type: 'float'
  default_value: 0.25000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 reflection_wavelet_scale_1

  data_type: 'float'
  default_value: 2.0000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,7]

=head2 reflection_wavelet_scale_2

  data_type: 'float'
  default_value: 2.0000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,7]

=head2 reflection_wavelet_scale_3

  data_type: 'float'
  default_value: 2.0000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,7]

=head2 fresnel_scale

  data_type: 'float'
  default_value: 0.40000001
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 fresnel_offset

  data_type: 'float'
  default_value: 0.50000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 refract_scale_above

  data_type: 'float'
  default_value: 0.03000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 refract_scale_below

  data_type: 'float'
  default_value: 0.20000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 blur_multiplier

  data_type: 'float'
  default_value: 0.04000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 big_wave_direction_x

  data_type: 'float'
  default_value: 1.04999995
  is_nullable: 0
  size: [9,8]

=head2 big_wave_direction_y

  data_type: 'float'
  default_value: -0.41999999
  is_nullable: 0
  size: [9,8]

=head2 little_wave_direction_x

  data_type: 'float'
  default_value: 1.11000001
  is_nullable: 0
  size: [9,8]

=head2 little_wave_direction_y

  data_type: 'float'
  default_value: -1.15999997
  is_nullable: 0
  size: [9,8]

=head2 normal_map_texture

  data_type: 'varchar'
  default_value: '822ded49-9a6c-f61c-cb89-6df54f42cdf4'
  is_nullable: 0
  size: 36

=head2 horizon_r

  data_type: 'float'
  default_value: 0.25000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 horizon_g

  data_type: 'float'
  default_value: 0.25000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 horizon_b

  data_type: 'float'
  default_value: 0.31999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 horizon_i

  data_type: 'float'
  default_value: 0.31999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 haze_horizon

  data_type: 'float'
  default_value: 0.19000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 blue_density_r

  data_type: 'float'
  default_value: 0.12000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 blue_density_g

  data_type: 'float'
  default_value: 0.22000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 blue_density_b

  data_type: 'float'
  default_value: 0.38000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 blue_density_i

  data_type: 'float'
  default_value: 0.38000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 haze_density

  data_type: 'float'
  default_value: 0.69999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 density_multiplier

  data_type: 'float'
  default_value: 0.18000001
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 distance_multiplier

  data_type: 'float'
  default_value: 0.800000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,6]

=head2 max_altitude

  data_type: 'integer'
  default_value: 1605
  extra: {unsigned => 1}
  is_nullable: 0

=head2 sun_moon_color_r

  data_type: 'float'
  default_value: 0.23999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_moon_color_g

  data_type: 'float'
  default_value: 0.25999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_moon_color_b

  data_type: 'float'
  default_value: 0.30000001
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_moon_color_i

  data_type: 'float'
  default_value: 0.30000001
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_moon_position

  data_type: 'float'
  default_value: 0.31700000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 ambient_r

  data_type: 'float'
  default_value: 0.34999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 ambient_g

  data_type: 'float'
  default_value: 0.34999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 ambient_b

  data_type: 'float'
  default_value: 0.34999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 ambient_i

  data_type: 'float'
  default_value: 0.34999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 east_angle

  data_type: 'float'
  default_value: 0.00000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_glow_focus

  data_type: 'float'
  default_value: 0.10000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 sun_glow_size

  data_type: 'float'
  default_value: 1.75000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 scene_gamma

  data_type: 'float'
  default_value: 1.0000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,7]

=head2 star_brightness

  data_type: 'float'
  default_value: 0.00000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_color_r

  data_type: 'float'
  default_value: 0.41000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_color_g

  data_type: 'float'
  default_value: 0.41000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_color_b

  data_type: 'float'
  default_value: 0.41000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_color_i

  data_type: 'float'
  default_value: 0.41000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_x

  data_type: 'float'
  default_value: 1.00000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_y

  data_type: 'float'
  default_value: 0.52999997
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_density

  data_type: 'float'
  default_value: 1.00000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_coverage

  data_type: 'float'
  default_value: 0.27000001
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_scale

  data_type: 'float'
  default_value: 0.41999999
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_detail_x

  data_type: 'float'
  default_value: 1.00000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_detail_y

  data_type: 'float'
  default_value: 0.52999997
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_detail_density

  data_type: 'float'
  default_value: 0.12000000
  extra: {unsigned => 1}
  is_nullable: 0
  size: [9,8]

=head2 cloud_scroll_x

  data_type: 'float'
  default_value: 0.2000000
  is_nullable: 0
  size: [9,7]

=head2 cloud_scroll_x_lock

  data_type: 'tinyint'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 cloud_scroll_y

  data_type: 'float'
  default_value: 0.0100000
  is_nullable: 0
  size: [9,7]

=head2 cloud_scroll_y_lock

  data_type: 'tinyint'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 draw_classic_clouds

  data_type: 'tinyint'
  default_value: 1
  extra: {unsigned => 1}
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "region_id",
  {
    data_type => "varchar",
    default_value => "000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "water_color_r",
  {
    data_type => "float",
    default_value => "4.000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 6],
  },
  "water_color_g",
  {
    data_type => "float",
    default_value => "38.000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 6],
  },
  "water_color_b",
  {
    data_type => "float",
    default_value => "64.000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 6],
  },
  "water_fog_density_exponent",
  {
    data_type => "float",
    default_value => "4.0000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 7],
  },
  "underwater_fog_modifier",
  {
    data_type => "float",
    default_value => "0.25000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "reflection_wavelet_scale_1",
  {
    data_type => "float",
    default_value => "2.0000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 7],
  },
  "reflection_wavelet_scale_2",
  {
    data_type => "float",
    default_value => "2.0000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 7],
  },
  "reflection_wavelet_scale_3",
  {
    data_type => "float",
    default_value => "2.0000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 7],
  },
  "fresnel_scale",
  {
    data_type => "float",
    default_value => 0.40000001,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "fresnel_offset",
  {
    data_type => "float",
    default_value => "0.50000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "refract_scale_above",
  {
    data_type => "float",
    default_value => "0.03000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "refract_scale_below",
  {
    data_type => "float",
    default_value => "0.20000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "blur_multiplier",
  {
    data_type => "float",
    default_value => "0.04000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "big_wave_direction_x",
  {
    data_type => "float",
    default_value => 1.04999995,
    is_nullable => 0,
    size => [9, 8],
  },
  "big_wave_direction_y",
  {
    data_type => "float",
    default_value => -0.41999999,
    is_nullable => 0,
    size => [9, 8],
  },
  "little_wave_direction_x",
  {
    data_type => "float",
    default_value => 1.11000001,
    is_nullable => 0,
    size => [9, 8],
  },
  "little_wave_direction_y",
  {
    data_type => "float",
    default_value => -1.15999997,
    is_nullable => 0,
    size => [9, 8],
  },
  "normal_map_texture",
  {
    data_type => "varchar",
    default_value => "822ded49-9a6c-f61c-cb89-6df54f42cdf4",
    is_nullable => 0,
    size => 36,
  },
  "horizon_r",
  {
    data_type => "float",
    default_value => "0.25000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "horizon_g",
  {
    data_type => "float",
    default_value => "0.25000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "horizon_b",
  {
    data_type => "float",
    default_value => 0.31999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "horizon_i",
  {
    data_type => "float",
    default_value => 0.31999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "haze_horizon",
  {
    data_type => "float",
    default_value => "0.19000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "blue_density_r",
  {
    data_type => "float",
    default_value => "0.12000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "blue_density_g",
  {
    data_type => "float",
    default_value => "0.22000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "blue_density_b",
  {
    data_type => "float",
    default_value => "0.38000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "blue_density_i",
  {
    data_type => "float",
    default_value => "0.38000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "haze_density",
  {
    data_type => "float",
    default_value => 0.69999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "density_multiplier",
  {
    data_type => "float",
    default_value => 0.18000001,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "distance_multiplier",
  {
    data_type => "float",
    default_value => "0.800000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 6],
  },
  "max_altitude",
  {
    data_type => "integer",
    default_value => 1605,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "sun_moon_color_r",
  {
    data_type => "float",
    default_value => 0.23999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_moon_color_g",
  {
    data_type => "float",
    default_value => 0.25999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_moon_color_b",
  {
    data_type => "float",
    default_value => 0.30000001,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_moon_color_i",
  {
    data_type => "float",
    default_value => 0.30000001,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_moon_position",
  {
    data_type => "float",
    default_value => "0.31700000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "ambient_r",
  {
    data_type => "float",
    default_value => 0.34999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "ambient_g",
  {
    data_type => "float",
    default_value => 0.34999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "ambient_b",
  {
    data_type => "float",
    default_value => 0.34999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "ambient_i",
  {
    data_type => "float",
    default_value => 0.34999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "east_angle",
  {
    data_type => "float",
    default_value => "0.00000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_glow_focus",
  {
    data_type => "float",
    default_value => "0.10000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "sun_glow_size",
  {
    data_type => "float",
    default_value => "1.75000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "scene_gamma",
  {
    data_type => "float",
    default_value => "1.0000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 7],
  },
  "star_brightness",
  {
    data_type => "float",
    default_value => "0.00000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_color_r",
  {
    data_type => "float",
    default_value => "0.41000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_color_g",
  {
    data_type => "float",
    default_value => "0.41000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_color_b",
  {
    data_type => "float",
    default_value => "0.41000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_color_i",
  {
    data_type => "float",
    default_value => "0.41000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_x",
  {
    data_type => "float",
    default_value => "1.00000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_y",
  {
    data_type => "float",
    default_value => 0.52999997,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_density",
  {
    data_type => "float",
    default_value => "1.00000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_coverage",
  {
    data_type => "float",
    default_value => 0.27000001,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_scale",
  {
    data_type => "float",
    default_value => 0.41999999,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_detail_x",
  {
    data_type => "float",
    default_value => "1.00000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_detail_y",
  {
    data_type => "float",
    default_value => 0.52999997,
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_detail_density",
  {
    data_type => "float",
    default_value => "0.12000000",
    extra => { unsigned => 1 },
    is_nullable => 0,
    size => [9, 8],
  },
  "cloud_scroll_x",
  {
    data_type => "float",
    default_value => "0.2000000",
    is_nullable => 0,
    size => [9, 7],
  },
  "cloud_scroll_x_lock",
  {
    data_type => "tinyint",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "cloud_scroll_y",
  {
    data_type => "float",
    default_value => "0.0100000",
    is_nullable => 0,
    size => [9, 7],
  },
  "cloud_scroll_y_lock",
  {
    data_type => "tinyint",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "draw_classic_clouds",
  {
    data_type => "tinyint",
    default_value => 1,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</region_id>

=back

=cut

__PACKAGE__->set_primary_key("region_id");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:OHFaIkq6F2fuVPv+uy+S6g


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
