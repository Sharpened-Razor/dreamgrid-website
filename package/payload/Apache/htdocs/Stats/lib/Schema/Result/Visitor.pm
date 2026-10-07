package Schema::Result::Visitor;

use strict;
use warnings;

use parent 'DBIx::Class::Core';

__PACKAGE__->table("visitor");

__PACKAGE__->add_columns(
	
	'name',			{ data_type => "nvarchar", is_nullable => 0, size => 255 },
	'regionname',	{ data_type => "nvarchar", is_nullable => 0, size => 255 },
	'locationY',	{ data_type => "bigint", is_nullable => 0 },
	'locationX',	{ data_type => "bigint", is_nullable => 0 },
	'dateupdated',	{ data_type => "datetime", is_nullable => 0 },
);



1;
