use utf8;
package Robust::Schema::Result::Agentpref;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Agentpref

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<agentprefs>

=cut

__PACKAGE__->table("agentprefs");

=head1 ACCESSORS

=head2 principalid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 accessprefs

  data_type: 'char'
  default_value: 'M'
  is_nullable: 0
  size: 2

=head2 hoverheight

  data_type: 'double precision'
  default_value: 0.000000000000000000000000000
  is_nullable: 0
  size: [30,27]

=head2 language

  data_type: 'char'
  default_value: 'en-us'
  is_nullable: 0
  size: 5

=head2 languageispublic

  data_type: 'tinyint'
  default_value: 1
  is_nullable: 0

=head2 permeveryone

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 permgroup

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 permnextowner

  data_type: 'integer'
  default_value: 532480
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "principalid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "accessprefs",
  { data_type => "char", default_value => "M", is_nullable => 0, size => 2 },
  "hoverheight",
  {
    data_type => "double precision",
    default_value => "0.000000000000000000000000000",
    is_nullable => 0,
    size => [30, 27],
  },
  "language",
  { data_type => "char", default_value => "en-us", is_nullable => 0, size => 5 },
  "languageispublic",
  { data_type => "tinyint", default_value => 1, is_nullable => 0 },
  "permeveryone",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "permgroup",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "permnextowner",
  { data_type => "integer", default_value => 532480, is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</principalid>

=back

=cut

__PACKAGE__->set_primary_key("principalid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:jdYN2zG2oKx5rBh2YJ+1zg


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
