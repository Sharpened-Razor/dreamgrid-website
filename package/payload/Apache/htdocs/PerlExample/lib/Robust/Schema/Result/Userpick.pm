use utf8;
package Robust::Schema::Result::Userpick;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Userpick

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<userpicks>

=cut

__PACKAGE__->table("userpicks");

=head1 ACCESSORS

=head2 pickuuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 creatoruuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 toppick

  data_type: 'enum'
  extra: {list => ["true","false"]}
  is_nullable: 0

=head2 parceluuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 name

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 description

  data_type: 'text'
  is_nullable: 0

=head2 snapshotuuid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 user

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 originalname

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 simname

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 posglobal

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 sortorder

  data_type: 'integer'
  is_nullable: 0

=head2 enabled

  data_type: 'enum'
  extra: {list => ["true","false"]}
  is_nullable: 0

=head2 gatekeeper

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=cut

__PACKAGE__->add_columns(
  "pickuuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "creatoruuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "toppick",
  {
    data_type => "enum",
    extra => { list => ["true", "false"] },
    is_nullable => 0,
  },
  "parceluuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "name",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "description",
  { data_type => "text", is_nullable => 0 },
  "snapshotuuid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "user",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "originalname",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "simname",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "posglobal",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "sortorder",
  { data_type => "integer", is_nullable => 0 },
  "enabled",
  {
    data_type => "enum",
    extra => { list => ["true", "false"] },
    is_nullable => 0,
  },
  "gatekeeper",
  { data_type => "varchar", is_nullable => 1, size => 255 },
);

=head1 PRIMARY KEY

=over 4

=item * L</pickuuid>

=back

=cut

__PACKAGE__->set_primary_key("pickuuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:BtJHfAoF0Phn2Q/STWunvQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
