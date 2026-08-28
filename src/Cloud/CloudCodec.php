<?php
namespace ScratchByPHP\Cloud;

final class CloudCodec {
    public static function encode(string $text): string {
        $bytes = unpack('C*', $text) ?: [];
        $digits='1'; foreach ($bytes as $b) $digits .= str_pad((string)$b,3,'0',STR_PAD_LEFT);
        return $digits;
    }
    public static function decode(string $digits): string {
        if ($digits === '' || $digits[0] !== '1') throw new \InvalidArgumentException('Geçersiz CloudCodec verisi.');
        $digits = substr($digits,1); $out='';
        for ($i=0; $i+2<strlen($digits); $i+=3) $out .= chr((int)substr($digits,$i,3));
        return $out;
    }
}
