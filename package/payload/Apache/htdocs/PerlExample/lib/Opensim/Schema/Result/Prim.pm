use utf8;
package Opensim::Schema::Result::Prim;

# Created by DBIx::Class::Schema::Loader
# DO NOT MODIFY THE FIRST PART OF THIS FILE

=head1 NAME

Opensim::Schema::Result::Prim

=cut

use strict;
use warnings;

use base 'DBIx::Class::Core';

=head1 TABLE: C<prims>

=cut

__PACKAGE__->table("prims");

=head1 ACCESSORS

=head2 creationdate

  data_type: 'integer'
  is_nullable: 1

=head2 name

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 text

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 description

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 sitname

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 touchname

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 objectflags

  data_type: 'integer'
  is_nullable: 1

=head2 ownermask

  data_type: 'integer'
  is_nullable: 1

=head2 nextownermask

  data_type: 'integer'
  is_nullable: 1

=head2 groupmask

  data_type: 'integer'
  is_nullable: 1

=head2 everyonemask

  data_type: 'integer'
  is_nullable: 1

=head2 basemask

  data_type: 'integer'
  is_nullable: 1

=head2 positionx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 positiony

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 positionz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 grouppositionx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 grouppositiony

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 grouppositionz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 velocityx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 velocityy

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 velocityz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 angularvelocityx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 angularvelocityy

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 angularvelocityz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 accelerationx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 accelerationy

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 accelerationz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 rotationx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 rotationy

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 rotationz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 rotationw

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetoffsetx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetoffsety

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetoffsetz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetorientw

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetorientx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetorienty

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sittargetorientz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 uuid

  data_type: 'char'
  default_value: (empty string)
  is_nullable: 0
  size: 36

=head2 regionuuid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 creatorid

  data_type: 'varchar'
  default_value: (empty string)
  is_nullable: 0
  size: 255

=head2 ownerid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 groupid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 lastownerid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 scenegroupid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 payprice

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 paybutton1

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 paybutton2

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 paybutton3

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 paybutton4

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 loopedsound

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 loopedsoundgain

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 textureanimation

  data_type: 'blob'
  is_nullable: 1

=head2 omegax

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 omegay

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 omegaz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraeyeoffsetx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraeyeoffsety

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraeyeoffsetz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraatoffsetx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraatoffsety

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 cameraatoffsetz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 forcemouselook

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 scriptaccesspin

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 alloweddrop

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 dieatedge

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 saleprice

  data_type: 'integer'
  default_value: 10
  is_nullable: 0

=head2 saletype

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 colorr

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 colorg

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 colorb

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 colora

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 particlesystem

  data_type: 'blob'
  is_nullable: 1

=head2 clickaction

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 material

  data_type: 'tinyint'
  default_value: 3
  is_nullable: 0

=head2 collisionsound

  data_type: 'char'
  default_value: '00000000-0000-0000-0000-000000000000'
  is_nullable: 0
  size: 36

=head2 collisionsoundvolume

  data_type: 'double precision'
  default_value: 0
  is_nullable: 0

=head2 linknumber

  data_type: 'integer'
  default_value: 0
  is_nullable: 0

=head2 passtouches

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 mediaurl

  data_type: 'varchar'
  is_nullable: 1
  size: 255

=head2 dynattrs

  data_type: 'text'
  is_nullable: 1

=head2 physicsshapetype

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 density

  data_type: 'float'
  default_value: 1000
  is_nullable: 1

=head2 gravitymodifier

  data_type: 'float'
  default_value: 1
  is_nullable: 1

=head2 friction

  data_type: 'float'
  default_value: 0.6
  is_nullable: 1

=head2 restitution

  data_type: 'float'
  default_value: 0.5
  is_nullable: 1

=head2 keyframemotion

  data_type: 'blob'
  is_nullable: 1

=head2 attachedposx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 attachedposy

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 attachedposz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 passcollisions

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 vehicle

  data_type: 'text'
  is_nullable: 1

=head2 rotationaxislocks

  data_type: 'tinyint'
  default_value: 0
  is_nullable: 0

=head2 rezzerid

  data_type: 'char'
  is_nullable: 1
  size: 36

=head2 physinertia

  data_type: 'text'
  is_nullable: 1

=head2 sopanims

  data_type: 'blob'
  is_nullable: 1

=head2 standtargetx

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 standtargety

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 standtargetz

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 sitactrange

  data_type: 'float'
  default_value: 0
  is_nullable: 1

=head2 pseudocrc

  data_type: 'integer'
  default_value: 0
  is_nullable: 1

=cut

__PACKAGE__->add_columns(
  "creationdate",
  { data_type => "integer", is_nullable => 1 },
  "name",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "text",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "description",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "sitname",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "touchname",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "objectflags",
  { data_type => "integer", is_nullable => 1 },
  "ownermask",
  { data_type => "integer", is_nullable => 1 },
  "nextownermask",
  { data_type => "integer", is_nullable => 1 },
  "groupmask",
  { data_type => "integer", is_nullable => 1 },
  "everyonemask",
  { data_type => "integer", is_nullable => 1 },
  "basemask",
  { data_type => "integer", is_nullable => 1 },
  "positionx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "positiony",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "positionz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "grouppositionx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "grouppositiony",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "grouppositionz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "velocityx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "velocityy",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "velocityz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "angularvelocityx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "angularvelocityy",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "angularvelocityz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "accelerationx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "accelerationy",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "accelerationz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "rotationx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "rotationy",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "rotationz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "rotationw",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetoffsetx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetoffsety",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetoffsetz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetorientw",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetorientx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetorienty",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sittargetorientz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "uuid",
  { data_type => "char", default_value => "", is_nullable => 0, size => 36 },
  "regionuuid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "creatorid",
  { data_type => "varchar", default_value => "", is_nullable => 0, size => 255 },
  "ownerid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "groupid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "lastownerid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "scenegroupid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "payprice",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "paybutton1",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "paybutton2",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "paybutton3",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "paybutton4",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "loopedsound",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "loopedsoundgain",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "textureanimation",
  { data_type => "blob", is_nullable => 1 },
  "omegax",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "omegay",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "omegaz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraeyeoffsetx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraeyeoffsety",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraeyeoffsetz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraatoffsetx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraatoffsety",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "cameraatoffsetz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "forcemouselook",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "scriptaccesspin",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "alloweddrop",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "dieatedge",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "saleprice",
  { data_type => "integer", default_value => 10, is_nullable => 0 },
  "saletype",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "colorr",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "colorg",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "colorb",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "colora",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "particlesystem",
  { data_type => "blob", is_nullable => 1 },
  "clickaction",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "material",
  { data_type => "tinyint", default_value => 3, is_nullable => 0 },
  "collisionsound",
  {
    data_type => "char",
    default_value => "00000000-0000-0000-0000-000000000000",
    is_nullable => 0,
    size => 36,
  },
  "collisionsoundvolume",
  { data_type => "double precision", default_value => 0, is_nullable => 0 },
  "linknumber",
  { data_type => "integer", default_value => 0, is_nullable => 0 },
  "passtouches",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "mediaurl",
  { data_type => "varchar", is_nullable => 1, size => 255 },
  "dynattrs",
  { data_type => "text", is_nullable => 1 },
  "physicsshapetype",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "density",
  { data_type => "float", default_value => 1000, is_nullable => 1 },
  "gravitymodifier",
  { data_type => "float", default_value => 1, is_nullable => 1 },
  "friction",
  { data_type => "float", default_value => 0.6, is_nullable => 1 },
  "restitution",
  { data_type => "float", default_value => 0.5, is_nullable => 1 },
  "keyframemotion",
  { data_type => "blob", is_nullable => 1 },
  "attachedposx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "attachedposy",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "attachedposz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "passcollisions",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "vehicle",
  { data_type => "text", is_nullable => 1 },
  "rotationaxislocks",
  { data_type => "tinyint", default_value => 0, is_nullable => 0 },
  "rezzerid",
  { data_type => "char", is_nullable => 1, size => 36 },
  "physinertia",
  { data_type => "text", is_nullable => 1 },
  "sopanims",
  { data_type => "blob", is_nullable => 1 },
  "standtargetx",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "standtargety",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "standtargetz",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "sitactrange",
  { data_type => "float", default_value => 0, is_nullable => 1 },
  "pseudocrc",
  { data_type => "integer", default_value => 0, is_nullable => 1 },
);

=head1 PRIMARY KEY

=over 4

=item * L</uuid>

=back

=cut

__PACKAGE__->set_primary_key("uuid");


# Created by DBIx::Class::Schema::Loader v0.07049 @ 2022-01-14 22:22:10
# DO NOT MODIFY THIS OR ANYTHING ABOVE! md5sum:CgiAnVjWENbdbm9CYLqyzA


# You can replace this text with custom code or comments, and it will be preserved on regeneration
1;
