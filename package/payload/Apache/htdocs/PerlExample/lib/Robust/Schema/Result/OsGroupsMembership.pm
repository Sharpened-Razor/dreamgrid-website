use utf8;
package Robust::Schema::Result::OsGroupsMembership;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsMembership

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_membership>

=cut

__PACKAGE__->table("os_groups_membership");

=head1 ACCESSORS

=head2 groupid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 principalid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 selectedroleid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 contribution

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 listinprofile

  data_type: 'integer'
  default_value: 1
  is_nullable: 0

=head2 acceptnotices

  data_type: 'integer'
  default_value: 1
  is_nullable: 0

=head2 accesstoken

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=cut

__PACKAGE__->add_columns(
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "principalid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "selectedroleid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "contribution",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "listinprofile",
  { data_type => "integer", default_value => 1, is_nullable => 0 },
  "acceptnotices",
  { data_type => "integer", default_value => 1, is_nullable => 0 },
  "accesstoken",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</groupid>

=item * L</principalid>

=back

=cut

__PACKAGE__->set_primary_key("groupid", "principalid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:uQkp15t/Isdsa1u8y3aJ3Q


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
