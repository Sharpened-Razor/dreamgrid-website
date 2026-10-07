use utf8;
package Robust::Schema::Result::Classified;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Classified

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<classifieds>

=cut

__PACKAGE__->table("classifieds");

=head1 ACCESSORS

=head2 classifieduuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 creatoruuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 creationdate

  data_type: 'integer'
  is_nullable: 0

=head2 expirationdate

  data_type: 'integer'
  is_nullable: 0

=head2 category

  data_type: 'varchar'
  is_nullable: 0
  size: 20

=head2 name

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 description

  data_type: 'text'
  is_nullable: 0

=head2 parceluuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 parentestate

  data_type: 'integer'
  is_nullable: 0

=head2 snapshotuuid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 simname

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 posglobal

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 parcelname

  data_type: 'varchar'
  is_nullable: 0
  size: 255

=head2 classifiedflags

  data_type: 'integer'
  is_nullable: 0

=head2 priceforlisting

  data_type: 'integer'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "classifieduuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "creatoruuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "creationdate",
  { data_type => "integer", is_nullable => 0 },
  "expirationdate",
  { data_type => "integer", is_nullable => 0 },
  "category",
  { data_type => "varchar", is_nullable => 0, size => 20 },
  "name",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "description",
  { data_type => "text", is_nullable => 0 },
  "parceluuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "parentestate",
  { data_type => "integer", is_nullable => 0 },
  "snapshotuuid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "simname",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "posglobal",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "parcelname",
  { data_type => "varchar", is_nullable => 0, size => 255 },
  "classifiedflags",
  { data_type => "integer", is_nullable => 0 },
  "priceforlisting",
  { data_type => "integer", is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</classifieduuid>

=back

=cut

__PACKAGE__->set_primary_key("classifieduuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:jQIAik8ll4vGfml/KskMUw


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
