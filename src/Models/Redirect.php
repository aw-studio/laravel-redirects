<?php

namespace AwStudio\Redirects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class Redirect extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_url',
        'to_url',
        'http_status_code',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if (trim(strtolower($model->from_url), '/') == trim(strtolower($model->to_url), '/')) {
                throw new InvalidArgumentException('The old url cannot be the same as the new url: ' . $model->to_url);
            }

            if (array_key_exists('host', parse_url($model->from_url))) {
                throw new InvalidArgumentException('You can not redirect from an url.');
            }

            $model->trimStringAttributes($model);

            $model::whereFromUrl($model->to_url)->whereToUrl($model->from_url)->delete();

            $model->updateOldRedirectsToNewTarget($model->to_url);

            Cache::forget('redirects');
        });
    }

    /**
     * Trim whitespaces and trailing slashes from string attributes.
     *
     * @param  self $model
     * @return void
     */
    public function trimStringAttributes($model)
    {
        $attributes = collect($model->getAttributes())->map(function ($attribute) {
            if (is_string($attribute)) {
                return trim($attribute, ' /');
            }

            return $attribute;
        });

        $model->forceFill($attributes->toArray());
    }

    /**
     * Scope a query to only include active redirects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to filter by from_url.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string                                $url
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereFromUrl($query, string $url)
    {
        return $query->where('from_url', $url);
    }

    /**
     * Scope a query to filter by to_url.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string                                $url
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereToUrl($query, string $url)
    {
        return $query->where('to_url', $url);
    }

    /**
     * Updates all redirects previously pointing to a route, which is now
     * redirecting to a new target itself.
     *
     * @param  self   $redirect
     * @param  string $finalUrl
     * @return void
     */
    public function updateOldRedirectsToNewTarget(string $finalUrl)
    {
        $items = $this->whereToUrl($this->from_url)->get();

        foreach ($items as $item) {
            $item->update(['to_url' => $finalUrl]);
            $item->updateOldRedirectsToNewTarget($this, $finalUrl);
        }
    }
}
