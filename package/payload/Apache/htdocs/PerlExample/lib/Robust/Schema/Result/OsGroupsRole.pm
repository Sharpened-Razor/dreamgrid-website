use utf8;
package Robust::Schema::Result::OsGroupsRole;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsRole

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_roles>

=cut

__PACKAGE__->table("os_groups_roles");

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

=head2 name

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 description

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 title

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 powers

  data_type: 'bigint'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "roleid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "name",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "description",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "title",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "powers",
  {
    data_type => "bigint",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</groupid>

=item * L</roleid>

=back

=cut

__PACKAGE__->set_primary_key("groupid", "roleid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:5O3GykyXU4UmrwnApX+QOQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
