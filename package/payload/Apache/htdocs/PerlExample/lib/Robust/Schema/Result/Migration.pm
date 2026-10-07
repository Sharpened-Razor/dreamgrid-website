use utf8;
package Robust::Schema::Result::Migration;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Migration

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<migrations>

=cut

__PACKAGE__->table("migrations");

=head1 ACCESSORS

=head2 name

  data_type: 'varchar'
  is_nullable: 1
  size: 100

=head2 version

  data_type: 'integer'
  is_nullable: 1

=cut

__PACKAGE__->add_columns(
  "name",
  { data_type => "varchar", is_nullable => 1, size => 100 },
  "version",
  { data_type => "integer", is_nullable => 1 },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:vTcu6dYUjLESXqtRvHH2yg


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
