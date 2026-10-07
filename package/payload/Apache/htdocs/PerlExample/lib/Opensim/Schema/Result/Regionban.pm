use utf8;
package Opensim::Schema::Result::Regionban;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Regionban

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<regionban>

=cut

__PACKAGE__->table("regionban");

=head1 ACCESSORS

=head2 regionuuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 banneduuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 bannedip

  data_type: 'varchar'
  is_nullable: 0
  size: 16

=head2 bannediphostmask

  data_type: 'varchar'
  is_nullable: 0
  size: 16

=cut

__PACKAGE__->add_columns(
  "regionuuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "banneduuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "bannedip",
  { data_type => "varchar", is_nullable => 0, size => 16 },
  "bannediphostmask",
  { data_type => "varchar", is_nullable => 0, size => 16 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:gQ9mDgQu44zMBEtZD77Iww


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
