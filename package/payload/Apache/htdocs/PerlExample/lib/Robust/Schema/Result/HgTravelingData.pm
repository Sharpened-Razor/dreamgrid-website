use utf8;
package Robust::Schema::Result::HgTravelingData;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Robust::Schema::Result::HgTravelingData

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<hg_traveling_data>

=cut

__PACKAGE__->table("hg_traveling_data");

=head1 ACCESSORS

=head2 sessionid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 userid

  data_type: 'varchar'
  is_nullable: 0
  size: 36

=head2 gridexternalname

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 servicetoken

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 clientipaddress

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 16

=head2 myipaddress

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 16

=head2 tmstamp

  data_type: 'timestamp'
  datetime_undef_if_invalid: 1
  default_value: current_timestamp
  is_nullable: 0

=cut

__PACKAGE__->add_columns(
  "sessionid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "userid",
  { data_type => "varchar", is_nullable => 0, size => 36 },
  "gridexternalname",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "servicetoken",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "clientipaddress",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 16 },
  "myipaddress",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 16 },
  "tmstamp",
  {
    data_type => "timestamp",
    datetime_undef_if_invalid => 1,
    default_value => \"current_timestamp",
    is_nullable => 0,
  },
);

=head1 PRIMARY KEY

=over 4

=item * L</sessionid>

=back

=cut

__PACKAGE__->set_primary_key("sessionid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:23:40
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:ouuV9IS1NN3hbsw2IZJYmg


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
