use utf8;
package Opensim::Schema::Result::Bakedterrain;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Bakedterrain

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<bakedterrain>

=cut

__PACKAGE__->table("bakedterrain");

=head1 ACCESSORS

=head2 regionuuid

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 revision

  data_type: 'integer'
  is_nullable: 1

=head2 heightfield

  data_type: 'longblob'
  is_nullable: 1

=cut

__PACKAGE__->add_columns(
  "regionuuid",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "revision",
  { data_type => "integer", is_nullable => 1 },
  "heightfield",
  { data_type => "longblob", is_nullable => 1 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:qTcHpSEyNCoXciRZv6VMiw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
