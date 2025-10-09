<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class BusinessPartner extends Model
{
    use HasFactory;

    protected $connection = "mysql2";

    protected $table = "business_partner";

    protected $primaryKey = "bp_code";

    protected $keyType = "string";

    protected $fillable = [
        'bp_code',
        'parent_bp_code',
        'bp_name',
        'bp_status_desc',
        'bp_currency',
        'country',
        'adr_line_1',
        'adr_line_2',
        'adr_line_3',
        'adr_line_4',
        'bp_phone',
        'bp_fax',
        'bp_role',
        'bp_role_desc',
    ];

    public $timestamps = false;

    // Accessor untuk base bp_code (tanpa akhiran)
    public function getBaseBpCodeAttribute()
    {
        return preg_replace('/-\d+$/', '', $this->bp_code);
    }

    // Accessor untuk menggabungkan alamat
    public function getBpAddressAttribute()
    {
        $addressParts = array_filter([
            $this->adr_line_1,
            $this->adr_line_2,
            $this->adr_line_3,
            $this->adr_line_4
        ]);
        return implode(', ', $addressParts);
    }

    // Accessor untuk status aktif
    public function getStatusAttribute()
    {
        return $this->bp_status_desc === 'Active' ? 'active' : 'inactive';
    }

    // Scope untuk mendapatkan business partner yang aktif
    public function scopeActive($query)
    {
        return $query->where('bp_status_desc', 'Active');
    }

    // Scope untuk query bp_code parent & child - improved version
    public function scopeRelatedBpCodes($query, $bpCode)
    {
        if (empty($bpCode)) {
            return $query;
        }

        // Jika bp_code memiliki suffix (sistem lama), cari parent dan semua child
        if (preg_match('/-\d+$/', $bpCode)) {
            $base = preg_replace('/-\d+$/', '', $bpCode);
            return $query->where(function($q) use ($bpCode, $base) {
                $q->where('bp_code', $bpCode)  // bp_code yang dicari
                  ->orWhere('bp_code', $base)  // parent bp_code
                  ->orWhere('parent_bp_code', $base); // semua child dari parent
            });
        }

        // Jika bp_code tanpa suffix (sistem baru), cari parent dan semua child
        return $query->where(function($q) use ($bpCode) {
            $q->where('bp_code', $bpCode)  // bp_code yang dicari
              ->orWhere('parent_bp_code', $bpCode) // semua child
              ->orWhere('bp_code', 'like', $bpCode . '-%'); // semua child dengan suffix
        });
    }

    // Scope untuk mendapatkan semua parent records
    public function scopeParentRecords($query)
    {
        return $query->whereNull('parent_bp_code');
    }

    // Scope untuk mendapatkan semua child records
    public function scopeChildRecords($query)
    {
        return $query->whereNotNull('parent_bp_code');
    }

    // Method untuk mendapatkan semua related bp_codes sebagai array
    public static function getUnifiedBpCodes($bpCode)
    {
        if (empty($bpCode)) {
            return collect();
        }

        $base = preg_replace('/-\d+$/', '', $bpCode);
        
        return self::where(function($query) use ($bpCode, $base) {
            $query->where('bp_code', $bpCode)
                  ->orWhere('bp_code', $base)
                  ->orWhere('parent_bp_code', $base)
                  ->orWhere('bp_code', 'like', $base . '-%');
        })->pluck('bp_code');
    }

    // Method untuk mendapatkan unified partner data
    public static function getUnifiedPartnerData($bpCode)
    {
        if (empty($bpCode)) {
            return collect();
        }

        $base = preg_replace('/-\d+$/', '', $bpCode);
        
        return self::where(function($query) use ($bpCode, $base) {
            $query->where('bp_code', $bpCode)
                  ->orWhere('bp_code', $base)
                  ->orWhere('parent_bp_code', $base)
                  ->orWhere('bp_code', 'like', $base . '-%');
        })->get();
    }

    public function isParentRecord()
    {
        return is_null($this->parent_bp_code);
    }

    public function isChildRecord()
    {
        return !is_null($this->parent_bp_code);
    }

    // Method untuk mendapatkan parent record
    public function getParentRecord()
    {
        if ($this->isParentRecord()) {
            return $this;
        }
        return self::where('bp_code', $this->parent_bp_code)->first();
    }

    // Method untuk mendapatkan child records
    public function getChildRecords()
    {
        if ($this->isChildRecord()) {
            return collect();
        }
        return self::where('parent_bp_code', $this->bp_code)->get();
    }

    // Method untuk mendapatkan semua related records (parent + children)
    public function getAllRelatedRecords()
    {
        if ($this->isParentRecord()) {
            return self::where(function($query) {
                $query->where('bp_code', $this->bp_code)
                      ->orWhere('parent_bp_code', $this->bp_code);
            })->get();
        } else {
            $parent = $this->getParentRecord();
            if ($parent) {
                return $parent->getAllRelatedRecords();
            }
            return collect([$this]);
        }
    }

    // Relationship dengan Visitor jika diperlukan
    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class, 'visitor_from', 'bp_code');
    }
}
