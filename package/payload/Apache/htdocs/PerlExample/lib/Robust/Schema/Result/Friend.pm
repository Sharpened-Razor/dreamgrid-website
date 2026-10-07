use utf8;
package Robust::Schema::Result::Friend;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Friend

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<friends>

=cut

__PACKAGE__->table("friends");

=head1 ACCESSORS

=head2 principalid

  data_type: 'varchar'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 255

=head2 friend

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 flags

  data_type: 'varchar'
  default_value: 0
  is_nullable: 0
  size: 16

=head2 offered

  data_type: 'varchar'
  default_value: 0
  is_nullable: 0
  size: 32

=cut

__PACKAGE__->add_columns(
  "principalid",
  {
    data_type => "varchar",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 255,
  },
  "friend",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "flags",
  { data_type => "varchar", default_value => 0, is_nullable => 0, size => 16 },
  "offered",
  { data_type => "varchar", default_value => 0, is_nullable => 0, size => 32 },
);

=head1 PRIMARY KEY

=over 4

=item * L</principalid>

=item * L</friend>

=back

=cut

__PACKAGE__->set_primary_key("principalid", "friend");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:DBKPZmJ62/6/0AjADgLkiA


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
