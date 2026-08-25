<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $company
 * @property string|null $phone
 * @property string|null $portal_token_hash
 * @property Carbon|null $portal_token_expires_at
 * @property Carbon|null $portal_token_revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Project> $projects
 */
#[Fillable(['name', 'email', 'company', 'phone'])]
#[Hidden(['portal_token_hash'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Whether the portal link currently works. Expiry and revocation are
     * checked together so a caller cannot forget one of them.
     */
    public function hasActivePortalAccess(): bool
    {
        if ($this->portal_token_hash === null) {
            return false;
        }

        if ($this->portal_token_revoked_at !== null) {
            return false;
        }

        return $this->portal_token_expires_at === null
            || $this->portal_token_expires_at->isFuture();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'portal_token_expires_at' => 'datetime',
            'portal_token_revoked_at' => 'datetime',
        ];
    }
}
