use utf8;
package Robust::Schema::Result::Griduser;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Griduser

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<griduser>

=cut

__PACKAGE__->table("griduser");

=head1 ACCESSORS

=head2 userid

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 homeregionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 homeposition

  data_type: 'char'
  default_value: '<0,0,0>'
  is_nullable: 0
  size: 64

=head2 homelookat

  data_type: 'char'
  default_value: '<0,0,0>'
  is_nullable: 0
  size: 64

=head2 lastregionid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 lastposition

  data_type: 'char'
  default_value: '<0,0,0>'
  is_nullable: 0
  size: 64

=head2 lastlookat

  data_type: 'char'
  default_value: '<0,0,0>'
  is_nullable: 0
  size: 64

=head2 online

  data_type: 'char'
  default_value: 'false'
  is_nullable: 0
  size: 5

=head2 login

  data_type: 'char'
  default_value: 0
  is_nullable: 0
  size: 16

=head2 logout

  data_type: 'char'
  default_value: 0
  is_nullable: 0
  size: 16

=cut

__PACKAGE__->add_columns(
  "userid",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "homeregionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "homeposition",
  {
    data_type => "char",
    default_value => "<0,0,0>",
    is_nullable => 0,
    size => 64,
  },
  "homelookat",
  {
    data_type => "char",
    default_value => "<0,0,0>",
    is_nullable => 0,
    size => 64,
  },
  "lastregionid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "lastposition",
  {
    data_type => "char",
    default_value => "<0,0,0>",
    is_nullable => 0,
    size => 64,
  },
  "lastlookat",
  {
    data_type => "char",
    default_value => "<0,0,0>",
    is_nullable => 0,
    size => 64,
  },
  "online",
  { data_type => "char", default_value => "false", is_nullable => 0, size => 5 },
  "login",
  { data_type => "char", default_value => 0, is_nullable => 0, size => 16 },
  "logout",
  { data_type => "char", default_value => 0, is_nullable => 0, size => 16 },
);

=head1 PRIMARY KEY

=over 4

=item * L</userid>

=back

=cut

__PACKAGE__->set_primary_key("userid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:JfdOshqsiQuJ0Lhdlo+ssQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
