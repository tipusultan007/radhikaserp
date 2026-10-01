<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StatementToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'short_url',
        'customer_id',
        'start_date',
        'end_date',
        'expires_at',
        'access_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function createOrGetForCustomer(Customer $customer, ?string $startDate = null, ?string $endDate = null): self
    {
        $existing = self::where('customer_id', $customer->id)
            ->where('start_date', $startDate ?: null)
            ->where('end_date', $endDate ?: null)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        do {
            $token = Str::random(8);
        } while (self::where('token', $token)->exists());

        return self::create([
            'token' => $token,
            'customer_id' => $customer->id,
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'expires_at' => now()->addDays(90),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Resolves a clean, branded short URL (e.g. https://radhikastradeintl.com/s/xyz)
     * completely hiding the erp. subdomain and avoiding third-party delay/preview pages.
     */
    public function getShortUrl(): string
    {
        $baseDomain = env('SHORT_URL_BASE_DOMAIN');
        if (empty($baseDomain)) {
            // Default to primary brand website domain
            $baseDomain = 'https://radhikastradeintl.com';
        }

        return rtrim($baseDomain, '/') . '/s/' . $this->token;
    }
}
