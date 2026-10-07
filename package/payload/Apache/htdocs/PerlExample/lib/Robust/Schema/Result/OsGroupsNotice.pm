use utf8;
package Robust::Schema::Result::OsGroupsNotice;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::OsGroupsNotice

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<os_groups_notices>

=cut

__PACKAGE__->table("os_groups_notices");

=head1 ACCESSORS

=head2 groupid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 noticeid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 tmstamp

  data_type: 'integer'
  default_value: 0
  extra: {unsigned => 1}
  is_nullable: 0

=head2 fromname

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 subject

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 message

  data_type: 'text'
  is_nullable: 0

=head2 hasattachment

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 attachmenttype

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 attachmentname

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 128

=head2 attachmentitemid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 attachmentownerid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=cut

__PACKAGE__->add_columns(
  "groupid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "noticeid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "tmstamp",
  {
    data_type => "integer",
    default_value => 0,
    extra => { unsigned => 1 },
    is_nullable => 0,
  },
  "fromname",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "subject",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "message",
  { data_type => "text", is_nullable => 0 },
  "hasattachment",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "attachmenttype",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "attachmentname",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 128 },
  "attachmentitemid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "attachmentownerid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
);

=head1 PRIMARY KEY

=over 4

=item * L</noticeid>

=back

=cut

__PACKAGE__->set_primary_key("noticeid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:myZb5xk/1FoHBY9JZYfv9w


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
