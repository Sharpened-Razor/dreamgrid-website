use utf8;
package Robust::Schema::Result::OsGroupsRolemembership;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsRolemembership

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_rolemembership>

=cut

__PACKAGE__->table("os_groups_rolemembership");

=head1 ACCESSORS

=head2 groupid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 roleid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 principalid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=cut

__PACKAGE__->add_columns(
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "roleid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "principalid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
);

=head1 PRIMARY KEY

=over 4

=item * L</groupid>

=item * L</roleid>

=item * L</principalid>

=back

=cut

__PACKAGE__->set_primary_key("groupid", "roleid", "principalid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:F58JpP3t+Sd4dAW+bTh3qQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
