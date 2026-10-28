<?php

namespace App\Filament\Support;

use App\Support\LegacyMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait PreparesLegacyMedia
{
    /**
     * LegacyMedia reports errors by field name, but the form state lives under "data".
     * Without the prefix Livewire drops the messages and the save fails without any feedback.
     */
    protected function prepareLegacyMedia(array $data, ?Model $record = null): array
    {
        try {
            return LegacyMedia::prepare($data, static::getResource()::definition(), $record);
        } catch (ValidationException $exception) {
            $exception = ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $field): array => ['data.'.$field => $messages])
                ->all());
            $this->onValidationError($exception);
            $this->dispatch('form-validation-error', livewireId: $this->getId());

            throw $exception;
        }
    }
}
