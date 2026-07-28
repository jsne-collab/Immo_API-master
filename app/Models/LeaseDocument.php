<?php

namespace App\Models;

use Database\Factories\LeaseDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseDocument extends Model
{
    /** @use HasFactory<LeaseDocumentFactory> */
    use HasFactory;

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_ID_PROOF = 'id_proof';

    public const TYPE_STATE_OF_PREMISES = 'state_of_premises';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'lease_id',
        'document_type',
        'file_path',
        'uploaded_by',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
