use utf8;
package Robust::Schema::Result::Mutelist;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Mutelist

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<mutelist>

=cut

__PACKAGE__->table("mutelist");

=head1 ACCESSORS

=head2 agentid

  data_type: 'char'
  is_nullable: 0
  size: 36

=head2 muteid

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 mutename

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 64

=head2 mutetype

  data_type: 'integer'
  default_value: 1
  is_nullable: 0

=head2 muteflags

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 stamp

  data_type: 'integer'
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "agentid",
  { data_type => "char", is_nullable => 0, size => 36 },
  "muteid",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "mutename",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 64 },
  "mutetype",
  { data_type => "integer", default_value => 1, is_nullable => 0 },
  "muteflags",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "stamp",
  { data_type => "integer", is_nullable => 0 },
);

=head1 UNIQUE CONSTRAINTS

=head2 C<AgentID_2>

=over 4

=item * L</agentid>

=item * L</muteid>

=item * L</mutename>

=back

=cut

__PACKAGE__->add_unique_constraint("AgentID_2", ["agentid", "muteid", "mutename"]);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:oOGt3snZBx2vNqtL2GeuGQ


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
