<?php

namespace Kakaprodo\PresignedAction\Models\Traits;

use Illuminate\Support\Arr;

/**
 * @property array $settings
 */
trait HasFieldSettings
{

    /**
     * update a single setting key
     */
    public function setSetting(string $key, mixed $value, $shouldCommit = true)
    {
        $settings  = ($this->settings ?? []);

        Arr::set($settings, $key, $value);

        if ($shouldCommit) {
            $this->settings = $settings;
            $this->save();
        } else {
            $this->fill(['settings' => $settings]);
        }

        return $this;
    }

    /**
     * update many setting keys
     */
    public function setSettings(array $settingKeysAndValues, $shouldCommit = true)
    {
        $formattedSettings = [
            ...($this->settings ?? []),
            ...$settingKeysAndValues
        ];

        if ($shouldCommit) {
            $this->update(['settings' => $formattedSettings]);
        } else {
            $this->fill(['settings' => $formattedSettings]);
        }

        return $this;
    }

    /**
     * get a given setting by name, or by dotted names
     * eg: user.name
     */
    public function getSetting(string $settingName, $default = null)
    {
        $settings = $this->settings ?? [];

        return Arr::get($settings, $settingName,  $default);
    }

    /**
     * Delete a given setting by name, or by dotted names
     * eg: user.name
     *
     * @param array|string|int|float $settingNames
     * @param bool $shouldCommit
     */
    public function deleteSetting($settingNames, $shouldCommit = true)
    {
        $settings = $this->settings ?? [];

        Arr::forget($settings, $settingNames);

        if ($shouldCommit) {
            $this->update(['settings' => $settings]);
        } else {
            $this->fill(['settings' => $settings]);
        }

        return $this;
    }
}
