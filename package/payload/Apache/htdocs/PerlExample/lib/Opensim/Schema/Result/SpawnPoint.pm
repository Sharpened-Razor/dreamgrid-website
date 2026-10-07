use utf8;
package Opensim::Schema::Result::SpawnPoint;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::SpawnPoint

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<spawn_points>

=cut

__PACKAGE__->table("spawn_points");

=head1 ACCESSORS

=head2 regionid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 yaw

  data_type: 'float'
  is_nullable: 0

=head2 pitch

  data_type: 'float'
  is_nullable: 0

=head2 distance

  data_type: 'float'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "regionid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "yaw",
  { data_type => "float", is_nullable => 0 },
  "pitch",
  { data_type => "float", is_nullable => 0 },
  "distance",
  { data_type => "float", is_nullable => 0 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:Cr+FjdfaR1qxZhCg79ilaw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
