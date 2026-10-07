use utf8;
package Robust::Schema::Result::OsGroupsPrincipal;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsPrincipal

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_principals>

=cut

__PACKAGE__->table("os_groups_principals");

=head1 ACCESSORS

=head2 principalid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 activegroupid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=cut

__PACKAGE__->add_columns(
  "principalid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "activegroupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</principalid>

=back

=cut

__PACKAGE__->set_primary_key("principalid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:F+/iHv/oi6+k0VLPduiKzQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
