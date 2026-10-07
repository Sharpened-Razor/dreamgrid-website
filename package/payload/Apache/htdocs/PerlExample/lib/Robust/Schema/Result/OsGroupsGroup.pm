use utf8;
package Robust::Schema::Result::OsGroupsGroup;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsGroup

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_groups>

=cut

__PACKAGE__->table("os_groups_groups");

=head1 ACCESSORS

=head2 groupid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 location

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 name

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 charter

  data_type: 'text'
  is_nullable: 0

=head2 insigniaid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 founderid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 membershipfee

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 openenrollment

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 showinlist

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 allowpublish

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 maturepublish

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 ownerroleid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=cut

__PACKAGE__->add_columns(
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "location",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "name",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "charter",
  { data_type => "text", is_nullable => 0 },
  "insigniaid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "founderid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "membershipfee",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "openenrollment",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "showinlist",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "allowpublish",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "maturepublish",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "ownerroleid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
);

=head1 PRIMARY KEY

=over 4

=item * L</groupid>

=back

=cut

__PACKAGE__->set_primary_key("groupid");

=head1 UNIQUE CONSTRAINTS

=head2 C<Name>

=over 4

=item * L</name>

=back

=cut

__PACKAGE__->add_unique_constraint("Name", ["name"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:vLWgxDKM1PQbwEqDLCy1Ew


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
