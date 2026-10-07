use utf8;
package Robust::Schema::Result::OsGroupInvites;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupInvites

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_invites>

=cut

__PACKAGE__->table("os_groups_invites");

=head1 ACCESSORS

=head2 inviteid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

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

=head2 tmstamp

  data_type: 'timestamp'
  datetime_undef_if_invalid: 1
  default_value: current_timestamp
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "inviteid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "roleid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "principalid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "tmstamp",
  {
    data_type => "timestamp",
    datetime_undef_if_invalid => 1,
    default_value => \"current_timestamp",
    is_nullable => 0,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</inviteid>

=back

=cut

__PACKAGE__->set_primary_key("inviteid");

=head1 UNIQUE CONSTRAINTS

=head2 C<PrincipalGroup>

=over 4

=item * L</groupid>

=item * L</principalid>

=back

=cut

__PACKAGE__->add_unique_constraint("PrincipalGroup", ["groupid", "principalid"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:6nB18LDgHhzexZi10dti1A


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
