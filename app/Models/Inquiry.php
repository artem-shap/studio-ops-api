<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $company
 * @property string $message
 * @property string|null $budget_range
 * @property InquiryStatus $status
 * @property int|null $converted_client_id
 * @property int|null $converted_project_id
 * @property Carbon|null $converted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client|null $convertedClient
 * @property-read Project|null $convertedProject
 */
#[Fillable(['name', 'email', 'company', 'message', 'budget_range'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * Laravel pluralises "Inquiry" as "inquiries" only if told to.
     */
    protected $table = 'inquiries';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => InquiryStatus::New->value,
    ];

    /**
     * @return BelongsTo<Client, $this>
     */
    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }

    /**
     * The guard that makes conversion idempotent. A double-clicked button is a
     * normal user, not an edge case.
     */
    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    /**
     * @param  Builder<Inquiry>  $query
     * @return Builder<Inquiry>
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereIn('status', [InquiryStatus::New, InquiryStatus::Contacted]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'converted_at' => 'datetime',
        ];
    }
}
