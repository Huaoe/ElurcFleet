<?php

namespace Fleetbase\Membership\Models;

use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberIdentity extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'member_identities';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'wallet_address',
        'membership_status',
        'verified_at',
        'nft_token_account',
        'last_verified_at',
        'metadata'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REVOKED = 'revoked';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function profile()
    {
        return $this->hasOne(MemberProfile::class, 'member_identity_uuid', 'uuid');
    }

    public function isPending(): bool
    {
        return $this->membership_status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->membership_status === self::STATUS_VERIFIED;
    }

    public function isSuspended(): bool
    {
        return $this->membership_status === self::STATUS_SUSPENDED;
    }

    public function isRevoked(): bool
    {
        return $this->membership_status === self::STATUS_REVOKED;
    }

    public function markAsVerified(): void
    {
        $this->update([
            'membership_status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now()
        ]);
    }

    public function updateLastVerified(): void
    {
        $this->update([
            'last_verified_at' => now()
        ]);
    }
}
