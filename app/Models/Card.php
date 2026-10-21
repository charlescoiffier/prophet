<?php

namespace App\Models;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\VerdictStatus;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Une promesse ou une prédiction, citée mot pour mot, datée et sourcée.
 *
 * Le statut, l'auteur, la version et les dates de publication ne sont pas
 * « fillable » : ils ne changent que par les classes d'action.
 */
#[Fillable([
    'type', 'quote', 'context', 'stated_on', 'testable_statement',
    'verdict_criterion', 'deadline', 'deadline_nature',
])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => CardType::class,
            'status' => CardStatus::class,
            'deadline_nature' => DeadlineNature::class,
            'stated_on' => 'date',
            'deadline' => 'date',
            'email_confirmed_at' => 'datetime',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    /** @return BelongsTo<PublicFigure, $this> */
    public function publicFigure(): BelongsTo
    {
        return $this->belongsTo(PublicFigure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsToMany<Theme, $this> */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    /** @return HasMany<Source, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    /** @return HasMany<Verdict, $this> */
    public function verdicts(): HasMany
    {
        return $this->hasMany(Verdict::class);
    }

    /** @return HasOne<Verdict, $this> */
    public function approvedVerdict(): HasOne
    {
        return $this->hasOne(Verdict::class)->where('status', VerdictStatus::Approved);
    }

    /** @return HasOne<Verdict, $this> */
    public function pendingVerdict(): HasOne
    {
        return $this->hasOne(Verdict::class)->where('status', VerdictStatus::Proposed);
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @return MorphMany<ActivityLog, $this> */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('created_at');
    }

    /**
     * Vrai si l'utilisateur est l'auteur de la carte, par son compte ou par son adresse e-mail.
     */
    public function isAuthoredBy(User $user): bool
    {
        return $this->author_id === $user->getKey()
            || ($this->author_email !== null && strcasecmp($this->author_email, $user->email) === 0);
    }

    /**
     * Prévient l'auteur : son compte s'il en a un, sinon l'adresse e-mail laissée à la proposition.
     */
    public function notifyAuthor(Notification $notification): void
    {
        if ($this->author !== null) {
            $this->author->notify($notification);
        } elseif ($this->author_email !== null) {
            NotificationFacade::route('mail', $this->author_email)->notify($notification);
        }
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereIn('status', CardStatus::public());
    }

    /**
     * Recherche dans la citation : chaque mot tapé doit y figurer, sans tenir compte des majuscules
     * ni des accents (« electricite prix »). Les personnalités se choisissent par les filtres.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $search): void
    {
        $terms = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            $pattern = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

            $query->whereRaw('unaccent(lower(quote)) like unaccent(?)', [$pattern]);
        }
    }

    /**
     * Cartes publiées dont l'échéance est passée et qui attendent encore un verdict.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingVerdict(Builder $query): void
    {
        $query->where('status', CardStatus::Published)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', today());
    }
}
