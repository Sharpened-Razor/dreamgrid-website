use utf8;
package Robust::Schema::Result::Userprofile;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Userprofile

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<userprofile>

=cut

__PACKAGE__->table("userprofile");

=head1 ACCESSORS

=head2 useruuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 profilepartner

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 profileallowpublish

  data_type: 'binary'
  is_nullable: 0
  size: 1

=head2 profilematurepublish

  data_type: 'binary'
  is_nullable: 0
  size: 1

=head2 profileurl

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 profilewanttomask

  data_type: 'integer'
  is_nullable: 0

=head2 profilewanttotext

  data_type: 'text'
  is_nullable: 0

=head2 profileskillsmask

  data_type: 'integer'
  is_nullable: 0

=head2 profileskillstext

  data_type: 'text'
  is_nullable: 0

=head2 profilelanguages

  data_type: 'text'
  is_nullable: 0

=head2 profileimage

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 profileabouttext

  data_type: 'text'
  is_nullable: 0

=head2 profilefirstimage

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 profilefirsttext

  data_type: 'text'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "useruuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "profilepartner",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "profileallowpublish",
  { data_type => "binary", is_nullable => 0, size => 1 },
  "profilematurepublish",
  { data_type => "binary", is_nullable => 0, size => 1 },
  "profileurl",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "profilewanttomask",
  { data_type => "integer", is_nullable => 0 },
  "profilewanttotext",
  { data_type => "text", is_nullable => 0 },
  "profileskillsmask",
  { data_type => "integer", is_nullable => 0 },
  "profileskillstext",
  { data_type => "text", is_nullable => 0 },
  "profilelanguages",
  { data_type => "text", is_nullable => 0 },
  "profileimage",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "profileabouttext",
  { data_type => "text", is_nullable => 0 },
  "profilefirstimage",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "profilefirsttext",
  { data_type => "text", is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</useruuid>

=back

=cut

__PACKAGE__->set_primary_key("useruuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:RDpdibjESQLJ+FZe8Dxk6A


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
