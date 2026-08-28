<?php
namespace ScratchByPHP\Trending;

use ScratchByPHP\Scratch;

final class TurkishTrending {
    public const TURK_NEEDLE='türk';

    public function __construct(private Scratch $scratch) {}

    /**
     * Discovers Turkish-oriented studios, collects all their projects, then ranks them.
     *
     * $studioScan controls how many studio-search results are inspected across the
     * query variants. Projects inside accepted studios are paged until exhausted,
     * with a configurable per-studio safety ceiling.
     */
    public function get(int $limit=20,int $studioScan=120,array $options=[]): array {
        $limit=max(1,min(100,$limit));
        $studioScan=max(1,min(400,$studioScan));

        $options=array_merge([
            'language'=>'tr',
            'studio_queries'=>['türk','Türk','TÜRK'],
            'studio_modes'=>['trending','popular'],
            'studio_page_size'=>40,
            'project_page_size'=>40,
            'max_projects_per_studio'=>2000,
            'weights'=>['views'=>0.35,'loves'=>0.15,'favorites'=>0.10,'freshness'=>0.40],
            'freshness_half_life_days'=>45.0,
        ],$options);

        $passes=[];
        foreach ((array)$options['studio_queries'] as $query) {
            foreach ((array)$options['studio_modes'] as $mode) {
                $passes[]=['query'=>(string)$query,'mode'=>(string)$mode];
            }
        }

        if (!$passes) return [];

        $studios=[];
        $remaining=$studioScan;
        $passIndex=0;

        while ($remaining>0 && $passIndex<count($passes)) {
            $pass=$passes[$passIndex++];
            $perPass=max(1,(int)ceil($remaining/max(1,count($passes)-$passIndex+1)));
            $offset=0;
            $left=$perPass;

            while ($left>0 && $remaining>0) {
                $take=min((int)$options['studio_page_size'],$left,$remaining);
                $rows=$this->scratch->searchStudios(
                    (string)$pass['query'],
                    (string)$pass['mode'],
                    (string)$options['language'],
                    $take,
                    $offset
                );

                if (!$rows) break;

                foreach ($rows as $studio) {
                    if (!is_array($studio) || !isset($studio['id'])) continue;
                    $title=(string)($studio['title']??'');
                    if (!self::titleHasTurk($title)) continue;
                    $studios[(string)$studio['id']]=$studio;
                }

                $count=count($rows);
                $left-=$count;
                $remaining-=$count;
                $offset+=$count;
                if ($count<$take) break;
            }
        }

        if (!$studios) return [];

        $projects=self::collectProjects(
            array_values($studios),
            function(array $studio) use ($options): array {
                return $this->scratch
                    ->studio((string)$studio['id'])
                    ->allProjects((int)$options['project_page_size'],(int)$options['max_projects_per_studio']);
            }
        );

        return array_slice(self::rank($projects,$options),0,$limit);
    }

    public static function titleHasTurk(string $title): bool {
        if ($title==='') return false;
        if (function_exists('mb_stripos')) return mb_stripos($title,self::TURK_NEEDLE,0,'UTF-8')!==false;
        return stripos($title,'türk')!==false || stripos($title,'TÜRK')!==false;
    }

    /**
     * Collects and de-duplicates projects from studio rows. The callback is kept
     * injectable so the discovery layer can be unit-tested without network access.
     */
    public static function collectProjects(array $studios,callable $projectFetcher): array {
        $projects=[];

        foreach ($studios as $studio) {
            if (!is_array($studio) || !isset($studio['id'])) continue;
            $title=(string)($studio['title']??'');
            if (!self::titleHasTurk($title)) continue;

            $source=[
                'id'=>(string)$studio['id'],
                'title'=>$title,
            ];

            $rows=$projectFetcher($studio);
            if (!is_array($rows)) continue;

            foreach ($rows as $project) {
                if (!is_array($project) || !isset($project['id'])) continue;
                $id=(string)$project['id'];

                if (!isset($projects[$id])) {
                    $project['_turkish_trend_studios']=[];
                    $projects[$id]=$project;
                }

                $existing=$projects[$id]['_turkish_trend_studios']??[];
                $seen=false;
                foreach ($existing as $item) {
                    if ((string)($item['id']??'')===$source['id']) {$seen=true;break;}
                }
                if (!$seen) $projects[$id]['_turkish_trend_studios'][]=$source;
            }
        }

        return array_values($projects);
    }

    public static function rank(array $projects,array $options=[]): array {
        $options=array_merge([
            'weights'=>['views'=>0.35,'loves'=>0.15,'favorites'=>0.10,'freshness'=>0.40],
            'freshness_half_life_days'=>45.0,
        ],$options);

        if (!$projects) return [];

        $weights=$options['weights'];
        $sum=array_sum(array_map('floatval',$weights));
        if ($sum<=0) throw new \InvalidArgumentException('TurkishTrending weights toplamı sıfır olamaz.');
        foreach ($weights as $k=>$v) $weights[$k]=(float)$v/$sum;

        $signals=[];
        foreach ($projects as $i=>$project) {
            $stats=$project['stats']??[];
            $views=max(0,(int)($stats['views']??0));
            $loves=max(0,(int)($stats['loves']??0));
            $favorites=max(0,(int)($stats['favorites']??0));
            $shared=(string)($project['history']['shared']??$project['history']['created']??'');
            $days=self::ageDays($shared);
            $freshness=self::freshnessScore($days,(float)$options['freshness_half_life_days']);

            $signals[$i]=[
                'views_raw'=>$views,
                'loves_raw'=>$loves,
                'favorites_raw'=>$favorites,
                'views_log'=>log10($views+1),
                'loves_log'=>log10($loves+1),
                'favorites_log'=>log10($favorites+1),
                'freshness'=>$freshness,
                'age_days'=>$days,
            ];
        }

        foreach (['views_log','loves_log','favorites_log'] as $metric) {
            $values=array_column($signals,$metric);
            $min=min($values);
            $max=max($values);
            foreach ($signals as &$signal) {
                $signal[$metric.'_normalized']=$max>$min
                    ? (($signal[$metric]-$min)/($max-$min))*100.0
                    : 100.0;
            }
            unset($signal);
        }

        $ranked=[];
        foreach ($projects as $i=>$project) {
            $s=$signals[$i];
            $score=
                $s['views_log_normalized']*$weights['views']+
                $s['loves_log_normalized']*$weights['loves']+
                $s['favorites_log_normalized']*$weights['favorites']+
                $s['freshness']*$weights['freshness'];

            $sources=$project['_turkish_trend_studios']??[];
            unset($project['_turkish_trend_studios']);

            $project['turkish_trend']=[
                'score'=>round($score,4),
                'source'=>'turkish_studios',
                'source_studios'=>$sources,
                'signals'=>[
                    'views'=>$s['views_raw'],
                    'loves'=>$s['loves_raw'],
                    'favorites'=>$s['favorites_raw'],
                    'age_days'=>round($s['age_days'],2),
                    'views_score'=>round($s['views_log_normalized'],2),
                    'loves_score'=>round($s['loves_log_normalized'],2),
                    'favorites_score'=>round($s['favorites_log_normalized'],2),
                    'freshness_score'=>round($s['freshness'],2),
                ],
                'weights'=>$weights,
            ];
            $ranked[]=$project;
        }

        usort($ranked,static function(array $a,array $b): int {
            $sa=(float)($a['turkish_trend']['score']??0);
            $sb=(float)($b['turkish_trend']['score']??0);
            if ($sa===$sb) return ((int)($b['stats']['views']??0)) <=> ((int)($a['stats']['views']??0));
            return $sb <=> $sa;
        });

        foreach ($ranked as $index=>&$project) $project['turkish_trend']['rank']=$index+1;
        unset($project);

        return $ranked;
    }

    private static function ageDays(string $date): float {
        if ($date==='') return 3650.0;
        try {
            $dt=new \DateTimeImmutable($date);
            $now=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
            return max(0,$now->getTimestamp()-$dt->getTimestamp())/86400;
        } catch (\Throwable) {
            return 3650.0;
        }
    }

    private static function freshnessScore(float $days,float $halfLife): float {
        return 100.0*pow(0.5,$days/max(1.0,$halfLife));
    }
}
