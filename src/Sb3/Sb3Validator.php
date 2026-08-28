<?php
namespace ScratchByPHP\Sb3;
final class Sb3Validator
{
    public function validate(string $path):array
    {
        $errors=[];$warnings=[];$archive=Sb3Archive::open($path);try{$j=$archive->projectJson();}catch(\Throwable $e){return ['ok'=>false,'errors'=>[$e->getMessage()],'warnings'=>[],'stats'=>[]];}
        if(!isset($j['targets'])||!is_array($j['targets']))$errors[]='project.json targets alanı eksik/geçersiz.';
        $assetRefs=[];$duplicateRefs=[];$blocks=0;$targets=0;
        foreach($j['targets']??[] as $ti=>$t){$targets++;if(!isset($t['name']))$warnings[]="Target #$ti name alanı eksik.";$blocks+=is_array($t['blocks']??null)?count($t['blocks']):0;foreach(array_merge($t['costumes']??[],$t['sounds']??[]) as $a){$md5=(string)($a['md5ext']??'');if($md5===''){$warnings[]='Bir asset md5ext alanı taşımıyor.';continue;}if(isset($assetRefs[$md5]))$duplicateRefs[$md5]=true;$assetRefs[$md5]=true;}}
        if(class_exists('ZipArchive')&&is_file($path)){ $z=new \ZipArchive(); if($z->open($path)===true){foreach(array_keys($assetRefs) as $a)if($z->locateName($a)===false)$errors[]='Eksik asset: '.$a;$z->close();}}
        if($duplicateRefs)$warnings[]='Birden fazla yerde kullanılan asset referansları: '.implode(', ',array_slice(array_keys($duplicateRefs),0,10));
        return ['ok'=>!$errors,'errors'=>$errors,'warnings'=>$warnings,'stats'=>['targets'=>$targets,'blocks'=>$blocks,'asset_references'=>count($assetRefs),'duplicate_asset_references'=>count($duplicateRefs)]];
    }
}
