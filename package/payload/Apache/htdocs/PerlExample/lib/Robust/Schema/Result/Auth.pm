use utf8;
package Robust::Schema::Result::Auth;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Auth

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<auth>

=cut

__PACKAGE__->table("auth");

=head1 ACCESSORS

=head2 uuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 passwordhash

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 32

=head2 passwordsalt

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 32

=head2 webloginkey

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 accounttype

  data_type: 'varchar'
  default_value: 'UserAccount'
  is_nullable: 0
  size: 32

=cut

__PACKAGE__->add_columns(
  "uuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "passwordhash",
  { data_type => "char", default_value => "", is_nullable => 0, size => 32 },
  "passwordsalt",
  { data_type => "char", default_value => "", is_nullable => 0, size => 32 },
  "webloginkey",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "accounttype",
  {
    data_type => "varchar",
    default_value => "UserAccount",
    is_nullable => 0,
    size => 32,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</uuid>

=back

=cut

__PACKAGE__->set_primary_key("uuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:QQPodNkczBXCNGC00fQ6AQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
