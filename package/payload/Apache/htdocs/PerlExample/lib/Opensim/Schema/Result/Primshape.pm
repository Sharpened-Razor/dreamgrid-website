use utf8;
package Opensim::Schema::Result::Primshape;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Primshape

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<primshapes>

=cut

__PACKAGE__->table("primshapes");

=head1 ACCESSORS

=head2 shape

  data_type: 'integer'
  is_nullable: 1

=head2 scalex

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 scaley

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 scalez

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 pcode

  data_type: 'integer'
  is_nullable: 1

=head2 pathbegin

  data_type: 'integer'
  is_nullable: 1

=head2 pathend

  data_type: 'integer'
  is_nullable: 1

=head2 pathscalex

  data_type: 'integer'
  is_nullable: 1

=head2 pathscaley

  data_type: 'integer'
  is_nullable: 1

=head2 pathshearx

  data_type: 'integer'
  is_nullable: 1

=head2 pathsheary

  data_type: 'integer'
  is_nullable: 1

=head2 pathskew

  data_type: 'integer'
  is_nullable: 1

=head2 pathcurve

  data_type: 'integer'
  is_nullable: 1

=head2 pathradiusoffset

  data_type: 'integer'
  is_nullable: 1

=head2 pathrevolutions

  data_type: 'integer'
  is_nullable: 1

=head2 pathtaperx

  data_type: 'integer'
  is_nullable: 1

=head2 pathtapery

  data_type: 'integer'
  is_nullable: 1

=head2 pathtwist

  data_type: 'integer'
  is_nullable: 1

=head2 pathtwistbegin

  data_type: 'integer'
  is_nullable: 1

=head2 profilebegin

  data_type: 'integer'
  is_nullable: 1

=head2 profileend

  data_type: 'integer'
  is_nullable: 1

=head2 profilecurve

  data_type: 'integer'
  is_nullable: 1

=head2 profilehollow

  data_type: 'integer'
  is_nullable: 1

=head2 state

  data_type: 'integer'
  is_nullable: 1

=head2 texture

  data_type: 'longblob'
  is_nullable: 1

=head2 extraparams

  data_type: 'longblob'
  is_nullable: 1

=head2 uuid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 media

  data_type: 'text'
  is_nullable: 1

=head2 lastattachpoint

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "shape",
  { data_type => "integer", is_nullable => 1 },
  "scalex",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "scaley",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "scalez",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "pcode",
  { data_type => "integer", is_nullable => 1 },
  "pathbegin",
  { data_type => "integer", is_nullable => 1 },
  "pathend",
  { data_type => "integer", is_nullable => 1 },
  "pathscalex",
  { data_type => "integer", is_nullable => 1 },
  "pathscaley",
  { data_type => "integer", is_nullable => 1 },
  "pathshearx",
  { data_type => "integer", is_nullable => 1 },
  "pathsheary",
  { data_type => "integer", is_nullable => 1 },
  "pathskew",
  { data_type => "integer", is_nullable => 1 },
  "pathcurve",
  { data_type => "integer", is_nullable => 1 },
  "pathradiusoffset",
  { data_type => "integer", is_nullable => 1 },
  "pathrevolutions",
  { data_type => "integer", is_nullable => 1 },
  "pathtaperx",
  { data_type => "integer", is_nullable => 1 },
  "pathtapery",
  { data_type => "integer", is_nullable => 1 },
  "pathtwist",
  { data_type => "integer", is_nullable => 1 },
  "pathtwistbegin",
  { data_type => "integer", is_nullable => 1 },
  "profilebegin",
  { data_type => "integer", is_nullable => 1 },
  "profileend",
  { data_type => "integer", is_nullable => 1 },
  "profilecurve",
  { data_type => "integer", is_nullable => 1 },
  "profilehollow",
  { data_type => "integer", is_nullable => 1 },
  "state",
  { data_type => "integer", is_nullable => 1 },
  "texture",
  { data_type => "longblob", is_nullable => 1 },
  "extraparams",
  { data_type => "longblob", is_nullable => 1 },
  "uuid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "media",
  { data_type => "text", is_nullable => 1 },
  "lastattachpoint",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
);

=head1 PRIMARY KEY

=over 4

=item * L</uuid>

=back

=cut

__PACKAGE__->set_primary_key("uuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:3SiQQNxN0hOThcrlLOCnnQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
