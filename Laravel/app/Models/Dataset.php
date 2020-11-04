<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;


class Dataset extends Model
{
    use HasApiTokens;
    use HasFactory;

     /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'max_value'=> null,
        'min_value'=>null,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'long_name',
        'data_type',
        'max_value',
        'min_value',
        'source_link',
        'source_description',
        'notes',
        'unit_description',
        'category',
        'distribution_map',
        'missing_data_percentage',
        "afg",
"ala",
"alb",
"dza",
"and",
"ago",
"aia",
"atg",
"arg",
"arm",
"abw",
"aus",
"aut",
"aze",
"bhs",
"bhr",
"bgd",
"brb",
"blr",
"bel",
"blz",
"ben",
"btn",
"bol",
"bes",
"bih",
"bwa",
"bra",
"brn",
"bgr",
"bfa",
"bdi",
"cpv",
"khm",
"cmr",
"can",
"cym",
"caf",
"tcd",
"chl",
"chn",
"col",
"com",
"cog",
"cod",
"cok",
"cri",
"civ",
"hrv",
"cub",
"cuw",
"cyp",
"cze",
"dnk",
"dji",
"dma",
"dom",
"ecu",
"egy",
"slv",
"gnq",
"eri",
"est",
"swz",
"eth",
"flk",
"fro",
"fji",
"fin",
"fra",
"pyf",
"gab",
"gmb",
"geo",
"deu",
"gha",
"gib",
"grc",
"grl",
"grd",
"gtm",
"ggy",
"gin",
"gnb",
"guy",
"hti",
"vat",
"hnd",
"hun",
"isl",
"ind",
"idn",
"irn",
"irq",
"irl",
"imn",
"isr",
"ita",
"jam",
"jpn",
"jey",
"jor",
"kaz",
"ken",
"kir",
"prk",
"kor",
"xxk",
"kwt",
"kgz",
"lao",
"lva",
"lbn",
"lso",
"lbr",
"lby",
"lie",
"ltu",
"lux",
"mdg",
"mwi",
"mys",
"mdv",
"mli",
"mlt",
"mhl",
"mrt",
"mus",
"mex",
"fsm",
"mda",
"mco",
"mng",
"mne",
"mar",
"moz",
"mmr",
"nam",
"nru",
"npl",
"nld",
"nzl",
"nic",
"ner",
"nga",
"niu",
"mkd",
"nor",
"omn",
"pak",
"plw",
"pse",
"pan",
"png",
"pry",
"per",
"phl",
"pol",
"prt",
"qat",
"rou",
"rus",
"rwa",
"blm",
"kna",
"lca",
"vct",
"wsm",
"smr",
"stp",
"sau",
"sen",
"srb",
"syc",
"sle",
"sgp",
"sxm",
"svk",
"svn",
"slb",
"som",
"zaf",
"ssd",
"esp",
"lka",
"sdn",
"sur",
"sjm",
"swe",
"che",
"syr",
"twn",
"tjk",
"tza",
"tha",
"tls",
"tgo",
"ton",
"tto",
"tun",
"tur",
"tkm",
"tca",
"tuv",
"uga",
"ukr",
"are",
"gbr",
"usa",
"ury",
"uzb",
"vut",
"ven",
"vnm",
"vgb",
"yem",
"zmb",
"zwe"
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'distribution_map' => 'array'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];


}
