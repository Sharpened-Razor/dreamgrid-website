use utf8;
package Robust::Schema::Result::Presence;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Presence

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<presence>

=cut

__PACKAGE__->table("presence");

=head1 ACCESSORS

=head2 userid

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 regionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 sessionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 securesessionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 lastseen

  data_type: 'timestamp'
  datetime_undef_if_invalid: 1
  default_value: current_timestamp
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "userid",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "regionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "sessionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "securesessionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "lastseen",
  {
    data_type => "timestamp",
    datetime_undef_if_invalid => 1,
    default_value => \"current_timestamp",
    is_nullable => 0,
  },
);

=head1 UNIQUE CONSTRAINTS

=head2 C<SessionID>

=over 4

=item * L</sessionid>

=back

=cut

__PACKAGE__->add_unique_constraint("SessionID", ["sessionid"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:HmFzvbEb/Uqx5LrCZ9HS4w


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
