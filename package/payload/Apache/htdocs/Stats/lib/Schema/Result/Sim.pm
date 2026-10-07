package Schema::Result::Sim;

use strict;
use warnings;

use parent 'DBIx::Class::Core';

__PACKAGE__->table('stats');

__PACKAGE__->add_columns(
	
	"regionname",		{ data_type => "nvarchar", is_nullable => 0, size => 255 },
	"regionsize",		{ data_type => "integer", is_nullable => 0 },
	"locationX",		{ data_type => "integer", is_nullable => 0 },
	"locationY",		{ data_type => "integer", is_nullable => 0 },
	 "dateupdated",		{ data_type => "datetime", is_nullable => 0},
	 "UUID",			{ data_type => "nvarchar", is_nullable => 0, size => 36 },
);

__PACKAGE__->set_primary_key('regionname');
__PACKAGE__->has_many('visitors' => ':Schema::Result::Visitor',{'foreign.regionname' => 'self.regionname'});



1;



