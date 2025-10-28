<?php

namespace App\Model;

class LogData extends Model
{
    protected ?string $table = 'log_data';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'tag',
        'value',
        'created_at',
    ];

    public bool $timestamps = false;


    public static function table($name, $date, $data) {

    }
}