<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'read_at',
        'replied_at',
        'archived_at',
        'admin_note',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            'new' => __('admin.contact_status_new'),
            'read' => __('admin.contact_status_read'),
            'replied' => __('admin.contact_status_replied'),
            'archived' => __('admin.contact_status_archived'),
        ];
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => $this->read_at ?: now(),
        ]);
    }

    public function markAsReplied(): void
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => $this->replied_at ?: now(),
        ]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => $this->archived_at ?: now(),
        ]);
    }
}