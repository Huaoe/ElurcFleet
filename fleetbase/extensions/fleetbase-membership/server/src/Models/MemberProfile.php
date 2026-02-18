<?php

namespace Fleetbase\Membership\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberProfile extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'member_profiles';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'member_identity_uuid',
        'store_uuid',
        'display_name',
        'avatar_url',
        'bio',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function memberIdentity()
    {
        return $this->belongsTo(MemberIdentity::class, 'member_identity_uuid', 'uuid');
    }

    public function store()
    {
        return $this->belongsTo(\Fleetbase\Storefront\Models\Store::class, 'store_uuid', 'uuid');
    }

    public function getDisplayNameAttribute($value): string
    {
        return $value ?? 'Anonymous Member';
    }

    public function setDisplayNameAttribute($value): void
    {
        $maxLength = config('membership.validation.display_name_max_length', 50);
        $this->attributes['display_name'] = substr($value, 0, $maxLength);
    }

    public function setBioAttribute($value): void
    {
        if ($value) {
            $maxLength = config('membership.validation.bio_max_length', 500);
            $this->attributes['bio'] = substr($value, 0, $maxLength);
        } else {
            $this->attributes['bio'] = null;
        }
    }
}
