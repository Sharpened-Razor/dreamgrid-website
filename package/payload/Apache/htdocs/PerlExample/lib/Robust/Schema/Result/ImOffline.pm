use utf8;
package Robust::Schema::Result::ImOffline;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::ImOffline

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<im_offline>

=cut

__PACKAGE__->table("im_offline");

=head1 ACCESSORS

=head2 id

  data_type: 'mediumint'
  is_auto_increment: 1
  is_nullable: 0

=head2 principalid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 fromid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 message

  data_type: 'text'
  is_nullable: 0

=head2 tmstamp

  data_type: 'timestamp'
  datetime_undef_if_invalid: 1
  default_value: current_timestamp
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "id",
  { data_type => "mediumint", is_auto_increment => 1, is_nullable => 0 },
  "principalid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "fromid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "message",
  { data_type => "text", is_nullable => 0 },
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

=item * L</id>

=back

=cut

__PACKAGE__->set_primary_key("id");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:PbJBY2XTWcrKYX6sV8gd5g


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
