<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Officer extends Model
{
    protected $fillable = [
        'officer_album_id',
        'name',
        'position',
        'photo_path',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(OfficerAlbum::class, 'officer_album_id');
    }

    /**
     * Lower numbers are higher offices. Specific titles are checked before broader ones.
     */
    public static function rank(string $position): int
    {
        $position = strtolower(trim($position));
        $ranks = [
            'immediate past president' => 20,
            'executive vice president' => 30,
            'vice president' => 40,
            'president' => 10,
            'secretary general' => 50,
            'secretary' => 60,
            'treasurer' => 70,
            'auditor' => 80,
            'legal counsel' => 90,
            'sergeant-at-arms' => 100,
            'sergeant at arms' => 100,
            'director' => 110,
            'chairperson' => 120,
            'chairman' => 120,
            'chair' => 130,
        ];

        foreach ($ranks as $title => $rank) {
            if (str_contains($position, $title)) {
                return $rank;
            }
        }

        return 500;
    }
}
