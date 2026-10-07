use utf8;
package Robust::Schema::Result::Stat;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::Stat

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<stats>

=cut

__PACKAGE__->table("stats");

=head1 ACCESSORS

=head2 regionname

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 regionsize

  data_type: 'integer'
  is_nullable: 1

=head2 locationx

  data_type: 'integer'
  is_nullable: 1

=head2 locationy

  data_type: 'integer'
  is_nullable: 1

=head2 uuid

  data_type: 'varchar'
  is_nullable: 1
  size: 36

=head2 dateupdated

  data_type: 'timestamp'
  datetime_undef_if_invalid: 1
  default_value: current_timestamp
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "regionname",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "regionsize",
  { data_type => "integer", is_nullable => 1 },
  "locationx",
  { data_type => "integer", is_nullable => 1 },
  "locationy",
  { data_type => "integer", is_nullable => 1 },
  "uuid",
  { data_type => "varchar", is_nullable => 1, size => 36 },
  "dateupdated",
  {
    data_type => "timestamp",
    datetime_undef_if_invalid => 1,
    default_value => \"current_timestamp",
    is_nullable => 0,
  },
);


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:9pxnlGVs05vHX9JG7Fbbog


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
