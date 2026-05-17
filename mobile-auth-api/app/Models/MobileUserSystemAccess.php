<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileUserSystemAccess extends Model
{
    protected $table = 'mobile_user_system_access';

    protected $fillable = [
        'mobile_user_id',
        'leave_user_id',
        'medical_user_id',
        'can_access_leave',
        'can_access_medical',
    ];

    protected function casts(): array
    {
        return [
            'can_access_leave' => 'boolean',
            'can_access_medical' => 'boolean',
        ];
    }

    public function mobileUser(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class);
    }
}
