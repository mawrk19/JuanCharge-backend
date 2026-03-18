<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldReportPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_report_id',
        'file_path',
        'file_url',
        'mime_type',
        'size_bytes',
    ];

    public function fieldReport()
    {
        return $this->belongsTo(FieldReport::class);
    }
}
