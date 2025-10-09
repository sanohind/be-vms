<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitor extends Model
{
    use HasFactory;

    protected $connection = "mysql";

    public $incrementing = false;

    protected $table = "visitor";

    protected $primaryKey = "visitor_id";

    protected $keyType = 'string';

    protected $fillable = [
        'visitor_id',
        'visitor_date',
        'visitor_name',
        'visitor_from',
        'bp_code',
        'visitor_host',
        'visitor_needs',
        'visitor_amount',
        'visitor_vehicle',
        'department',
        'visitor_img',
        'visitor_checkin',
        'visitor_checkout',
    ];

    public $timestamps = false;

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'visitor_host', 'name');
    }
}
