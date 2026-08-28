<?php
namespace ScratchByPHP\Cloud;

final class CloudDatabase {
    public function __construct(private CloudConnection $cloud, private string $variable='db') {}

    private function readMap(): array {
        $raw=$this->cloud->getRemote($this->variable,2.0);
        if (!$raw) return [];
        try {
            $json=CloudCodec::decode($raw);
            $map=json_decode($json,true);
            return is_array($map)?$map:[];
        } catch(\Throwable) {
            return [];
        }
    }

    private function writeMap(array $map): array {
        $encoded=CloudCodec::encode(json_encode($map,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        if (strlen($encoded)>256) throw new \RuntimeException('CloudDatabase verisi 256 basamak sınırını aşıyor. Daha küçük veri kullan.');
        return $this->cloud->setVerified($this->variable,$encoded,5.0);
    }

    public function all(): array { return $this->readMap(); }
    public function get(string $key,mixed $default=null): mixed { $m=$this->readMap(); return $m[$key]??$default; }
    public function set(string $key,mixed $value): array { $m=$this->readMap(); $m[$key]=$value; return $this->writeMap($m); }
    public function delete(string $key): array { $m=$this->readMap(); unset($m[$key]); return $this->writeMap($m); }
    public function has(string $key): bool { $m=$this->readMap(); return array_key_exists($key,$m); }
    public function increment(string $key,int|float $by=1): array { $m=$this->readMap();$m[$key]=(is_numeric($m[$key]??0)?($m[$key]??0):0)+$by;return $this->writeMap($m); }
    public function decrement(string $key,int|float $by=1): array { return $this->increment($key,-$by); }
    public function clear(): array { return $this->writeMap([]); }

    public function getToDB(mixed $database, ?string $table=null, array $options=[]): array {
        $map=$this->readMap();
        $config=self::databaseConfig($database,$table,$options);
        $plan=self::planToDB($map,$config);

        if (!empty($config['dry_run'])) {
            return $plan + ['executed'=>false,'dry_run'=>true];
        }

        $mysqli=self::resolveMysqli($database,$config);
        $ownsConnection=!(is_object($database) && class_exists('mysqli') && $database instanceof \mysqli);

        try {
            if (!empty($config['auto_create'])) self::createKvTableIfNeeded($mysqli,$config);

            $written=0;
            $mysqli->begin_transaction();
            try {
                $stmt=$mysqli->prepare($plan['sql']);
                if (!$stmt) throw new \RuntimeException('MySQL prepare başarısız: '.$mysqli->error);

                foreach ($plan['rows'] as $row) {
                    $key=(string)$row['key'];
                    $value=(string)$row['value'];
                    $stmt->bind_param('ss',$key,$value);
                    if (!$stmt->execute()) throw new \RuntimeException('MySQL execute başarısız: '.$stmt->error);
                    $written++;
                }

                $stmt->close();
                $mysqli->commit();
            } catch (\Throwable $e) {
                $mysqli->rollback();
                throw $e;
            }

            return [
                'success'=>true,
                'mode'=>'kv',
                'table'=>$config['table'],
                'written'=>$written,
                'keys'=>array_keys($map),
                'executed'=>true,
                'dry_run'=>false,
            ];
        } finally {
            if ($ownsConnection && $mysqli instanceof \mysqli) $mysqli->close();
        }
    }

    public function exportToMySQL(mixed $database, ?string $table=null, array $options=[]): array {
        return $this->getToDB($database,$table,$options);
    }

    public static function planToDB(array $map, array|string $database, ?string $table=null, array $options=[]): array {
        $config=is_array($database) && $table===null && $options===[] && isset($database['table'])
            ? self::databaseConfig($database,null,[])
            : self::databaseConfig($database,$table,$options);

        $rows=[];
        foreach ($map as $key=>$value) {
            $rows[]=['key'=>(string)$key,'value'=>self::databaseValue($value)];
        }

        $t=self::identifier($config['table']);
        $kc=self::identifier($config['key_column']);
        $vc=self::identifier($config['value_column']);
        $uc=$config['updated_at_column']!==null ? self::identifier($config['updated_at_column']) : null;

        $columns="`{$kc}`,`{$vc}`";
        $values='?,?';
        if ($uc!==null) { $columns.=",`{$uc}`"; $values.=',NOW()'; }

        $sql="INSERT INTO `{$t}` ({$columns}) VALUES ({$values})";
        if (!empty($config['upsert'])) {
            $sql.=" ON DUPLICATE KEY UPDATE `{$vc}`=VALUES(`{$vc}`)";
            if ($uc!==null) $sql.=", `{$uc}`=NOW()";
        }

        return [
            'mode'=>'kv',
            'table'=>$config['table'],
            'sql'=>$sql,
            'rows'=>$rows,
            'row_count'=>count($rows),
            'keys'=>array_map(static fn($r)=>$r['key'],$rows),
            'config'=>[
                'table'=>$config['table'],
                'key_column'=>$config['key_column'],
                'value_column'=>$config['value_column'],
                'updated_at_column'=>$config['updated_at_column'],
                'upsert'=>$config['upsert'],
                'auto_create'=>$config['auto_create'],
                'charset'=>$config['charset'],
            ],
        ];
    }

    private static function databaseConfig(mixed $database, ?string $table, array $options): array {
        $config=[];

        if (is_string($database)) {
            if (!is_file($database)) throw new \InvalidArgumentException('MySQL JSON config dosyası bulunamadı.');
            if (strtolower(pathinfo($database,PATHINFO_EXTENSION))!=='json') throw new \InvalidArgumentException('MySQL config dosyası .json olmalı.');
            $decoded=json_decode((string)file_get_contents($database),true);
            if (!is_array($decoded)) throw new \InvalidArgumentException('MySQL JSON config geçersiz.');
            $config=$decoded;
        } elseif (is_array($database)) {
            $config=$database;
        } elseif (is_object($database) && class_exists('mysqli') && $database instanceof \mysqli) {
            $config=[];
        } else {
            throw new \InvalidArgumentException('Database config JSON path, array veya mysqli instance olmalı.');
        }

        $config=array_merge([
            'host'=>'localhost',
            'port'=>3306,
            'username'=>'',
            'password'=>'',
            'database'=>'',
            'table'=>$table ?? 'scratch_cloud',
            'mode'=>'kv',
            'key_column'=>'cloud_key',
            'value_column'=>'cloud_value',
            'updated_at_column'=>'updated_at',
            'upsert'=>true,
            'auto_create'=>false,
            'charset'=>'utf8mb4',
            'dry_run'=>false,
        ],$config,$options);

        if ($table!==null) $config['table']=$table;
        if (($config['mode']??'kv')!=='kv') throw new \InvalidArgumentException('v0.8.5 CloudDB Pro şu an yalnızca kv modunu destekler.');

        self::identifier((string)$config['table']);
        self::identifier((string)$config['key_column']);
        self::identifier((string)$config['value_column']);
        if ($config['updated_at_column']!==null && $config['updated_at_column']!=='') self::identifier((string)$config['updated_at_column']);
        else $config['updated_at_column']=null;

        $config['port']=(int)$config['port'];
        $config['upsert']=(bool)$config['upsert'];
        $config['auto_create']=(bool)$config['auto_create'];
        $config['dry_run']=(bool)$config['dry_run'];

        return $config;
    }

    private static function resolveMysqli(mixed $database,array $config): \mysqli {
        if (!class_exists('mysqli')) throw new \RuntimeException('CloudDB Pro MySQL aktarımı için ext-mysqli gerekli.');

        if ($database instanceof \mysqli) {
            if (!empty($config['charset'])) $database->set_charset((string)$config['charset']);
            return $database;
        }

        foreach (['host','username','database'] as $required) {
            if (trim((string)$config[$required])==='') throw new \InvalidArgumentException("MySQL config alanı gerekli: {$required}");
        }

        $mysqli=new \mysqli(
            (string)$config['host'],
            (string)$config['username'],
            (string)$config['password'],
            (string)$config['database'],
            (int)$config['port']
        );

        if ($mysqli->connect_errno) throw new \RuntimeException('MySQL bağlantısı başarısız: '.$mysqli->connect_error);
        if (!empty($config['charset']) && !$mysqli->set_charset((string)$config['charset'])) {
            throw new \RuntimeException('MySQL charset ayarlanamadı: '.$mysqli->error);
        }

        return $mysqli;
    }

    private static function createKvTableIfNeeded(\mysqli $mysqli,array $config): void {
        $t=self::identifier($config['table']);
        $kc=self::identifier($config['key_column']);
        $vc=self::identifier($config['value_column']);
        $uc=$config['updated_at_column']!==null ? self::identifier($config['updated_at_column']) : null;

        $sql="CREATE TABLE IF NOT EXISTS `{$t}` (
            `{$kc}` VARCHAR(191) NOT NULL,
            `{$vc}` LONGTEXT NOT NULL";
        if ($uc!==null) $sql.=", `{$uc}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $sql.=", PRIMARY KEY (`{$kc}`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        if (!$mysqli->query($sql)) throw new \RuntimeException('CloudDB Pro tablo oluşturamadı: '.$mysqli->error);
    }

    private static function identifier(string $value): string {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/',$value)) {
            throw new \InvalidArgumentException('Geçersiz MySQL tablo/kolon adı: '.$value);
        }
        return $value;
    }

    private static function databaseValue(mixed $value): string {
        if (is_string($value)) return $value;
        if (is_int($value)||is_float($value)) return (string)$value;
        if (is_bool($value)) return $value?'1':'0';
        if ($value===null) return 'null';
        return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }
}
