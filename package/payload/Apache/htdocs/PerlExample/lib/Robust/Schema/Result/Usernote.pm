use utf8;
package Robust::Schema::Result::Usernote;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Usernote

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<usernotes>

=cut

__PACKAGE__->table("usernotes");

=head1 ACCESSORS

=head2 useruuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 targetuuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 notes

  data_type: 'text'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "useruuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "targetuuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "notes",
  { data_type => "text", is_nullable => 0 },
);

=head1 UNIQUE CONSTRAINTS

=head2 C<useruuid>

=over 4

=item * L</useruuid>

=item * L</targetuuid>

=back

=cut

__PACKAGE__->add_unique_constraint("useruuid", ["useruuid", "targetuuid"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:wq+YR3TYlvgYwiFdTpqamQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
