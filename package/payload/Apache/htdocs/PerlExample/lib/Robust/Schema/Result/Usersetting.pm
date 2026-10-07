use utf8;
package Robust::Schema::Result::Usersetting;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Usersetting

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<usersettings>

=cut

__PACKAGE__->table("usersettings");

=head1 ACCESSORS

=head2 useruuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 imviaemail

  data_type: 'enum'
  extra: {list => ["true","false"]}
  is_nullable: 0

=head2 visible

  data_type: 'enum'
  extra: {list => ["true","false"]}
  is_nullable: 0

=head2 email

  data_type: 'varchar'
  is_nullable: 0
  size: 254

=cut

__PACKAGE__->add_columns(
  "useruuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "imviaemail",
  {
    data_type => "enum",
    extra => { list => ["true", "false"] },
    is_nullable => 0,
  },
  "visible",
  {
    data_type => "enum",
    extra => { list => ["true", "false"] },
    is_nullable => 0,
  },
  "email",
  { data_type => "varchar", is_nullable => 0, size => 254 },
);

=head1 PRIMARY KEY

=over 4

=item * L</useruuid>

=back

=cut

__PACKAGE__->set_primary_key("useruuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:3brzLFMvFrfwTfS8CU8uZA


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
