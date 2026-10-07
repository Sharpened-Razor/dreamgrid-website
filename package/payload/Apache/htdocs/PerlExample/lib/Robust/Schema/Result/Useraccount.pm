use utf8;
package Robust::Schema::Result::Useraccount;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Useraccount

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<useraccounts>

=cut

__PACKAGE__->table("useraccounts");

=head1 ACCESSORS

=head2 principalid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 scopeid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 firstname

  data_type: 'varchar'
  is_nullable: 0
  size: 64

=head2 lastname

  data_type: 'varchar'
  is_nullable: 0
  size: 64

=head2 email

  data_type: 'varchar'
  is_nullable: 1
  size: 64

=head2 serviceurls

  data_type: 'text'
  is_nullable: 1

=head2 created

  data_type: 'integer'
  is_nullable: 1

=head2 userlevel

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 userflags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 usertitle

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 64

=head2 active

  data_type: 'integer'
  default_value: 1
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "principalid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "scopeid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "firstname",
  { data_type => "varchar", is_nullable => 0, size => 64 },
  "lastname",
  { data_type => "varchar", is_nullable => 0, size => 64 },
  "email",
  { data_type => "varchar", is_nullable => 1, size => 64 },
  "serviceurls",
  { data_type => "text", is_nullable => 1 },
  "created",
  { data_type => "integer", is_nullable => 1 },
  "userlevel",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "userflags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "usertitle",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 64 },
  "active",
  { data_type => "integer", default_value => 1, is_nullable => 0 },
);

=head1 UNIQUE CONSTRAINTS

=head2 C<PrincipalID>

=over 4

=item * L</principalid>

=back

=cut

__PACKAGE__->add_unique_constraint("PrincipalID", ["principalid"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:wGCGOcokxxDOeTo5fYVzLg


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
