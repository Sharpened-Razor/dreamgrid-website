<?php
/* Bounded, read-only ZIP reader. No extraction or optional PHP extensions. */
class AgTemplateZip {
    public $numFiles=0;
    private $bytes='', $entries=array(), $central=0;
    private function part($offset,$length){if($offset<0||$length<0||$offset+$length>strlen($this->bytes))throw new RuntimeException('The ZIP is truncated.');return substr($this->bytes,$offset,$length);}
    public function open($path){
        if(!is_file($path)||filesize($path)>33554432)throw new RuntimeException('Choose a template ZIP of 32 MB or smaller.');
        $this->bytes=file_get_contents($path);$end=null;$length=strlen($this->bytes);
        for($i=$length-22;$i>=max(0,$length-65557);$i--){if(substr($this->bytes,$i,4)==="PK\x05\x06"){$h=unpack('vdisk/vstart/vdiskCount/vcount/Vsize/Voffset/vcomment',$this->part($i+4,18));if($i+22+$h['comment']===$length){$end=$i;break;}}}
        if($end===null)throw new RuntimeException('The ZIP directory is missing.');
        if($h['disk']||$h['start']||$h['diskCount']!==$h['count']||$h['count']>500||$h['offset']+$h['size']!==$end)throw new RuntimeException('Split, ZIP64 or oversized ZIP directories are not supported.');
        $this->central=$h['offset'];$offset=$this->central;
        for($i=0;$i<$h['count'];$i++){
            if($this->part($offset,4)!=="PK\x01\x02")throw new RuntimeException('Invalid ZIP directory entry.');
            $s=unpack('vmade/vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vname/vextra/vcomment/vdisk/vinternal/Vattrs/Voffset',$this->part($offset+4,42));
            if($s['disk']||$s['size']>8388608||$s['compressed']>33554432||$s['offset']>=$this->central)throw new RuntimeException('Invalid or oversized ZIP entry.');
            if($s['flags']&1)throw new RuntimeException('Encrypted ZIP entries are not supported.');
            if(!in_array($s['method'],array(0,8),true))throw new RuntimeException('Use a ZIP with standard stored or deflated compression.');
            $s['filename']=$this->part($offset+46,$s['name']);$offset+=46+$s['name']+$s['extra']+$s['comment'];
            if($offset>$end)throw new RuntimeException('Invalid ZIP directory size.');$this->entries[]=$s;
        }
        if($offset!==$end)throw new RuntimeException('Invalid ZIP directory size.');$this->numFiles=count($this->entries);return true;
    }
    public function statIndex($i){$s=$this->entries[$i];return array('name'=>$s['filename'],'size'=>$s['size'],'comp_size'=>$s['compressed'],'encryption_method'=>0);}
    public function getExternalAttributesIndex($i,&$os,&$attrs){$s=$this->entries[$i];$os=$s['made']>>8;$attrs=$s['attrs'];return true;}
    public function getFromIndex($i){
        $s=$this->entries[$i];$offset=$s['offset'];if($this->part($offset,4)!=="PK\x03\x04")throw new RuntimeException('Invalid ZIP file header.');
        $local=unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vname/vextra',$this->part($offset+4,26));
        if($local['flags']!==$s['flags']||$local['method']!==$s['method']||$this->part($offset+30,$local['name'])!==$s['filename'])throw new RuntimeException('ZIP headers disagree.');
        if(!($s['flags']&8)&&($local['crc']!==$s['crc']||$local['size']!==$s['size']||$local['compressed']!==$s['compressed']))throw new RuntimeException('ZIP sizes disagree.');
        $start=$offset+30+$local['name']+$local['extra'];if($start+$s['compressed']>$this->central)throw new RuntimeException('ZIP data overlaps its directory.');
        $packed=$this->part($start,$s['compressed']);$bytes=$s['method']===0?$packed:@gzinflate($packed,max(1,$s['size']));
        if(!is_string($bytes)||strlen($bytes)!==$s['size']||sprintf('%u',crc32($bytes))!==sprintf('%u',$s['crc']))throw new RuntimeException('ZIP entry checksum or size failed.');return $bytes;
    }
    public function close(){$this->bytes='';$this->entries=array();$this->numFiles=0;}
}
